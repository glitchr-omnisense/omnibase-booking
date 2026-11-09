<?php

namespace Base\Booking\Checkout;

use Base\Booking\Entity\Booking;
use Base\Booking\Enum\PaymentState;
use Base\Booking\Exception\BookingException;
use Base\Booking\Payment\BookingPayments;
use Base\Booking\Payout\Payouts;
use Base\Booking\Repository\BookingRepository;
use Base\Booking\Stay\DoctrineListingStore;
use Base\Booking\Stay\ListingMapper;
use Base\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Omnistay\Exception\UnavailableException;
use Omnistay\Local\LocalProvider;
use Omnistay\Model\Availability;
use Omnistay\Model\BookingRequest;
use Omnistay\Model\BookingStatus;
use Omnistay\Model\CancellationPolicy;
use Omnistay\Model\Guests;
use Omnistay\Model\Money;
use Omnistay\Model\Offer;
use Omnistay\Model\PaymentCard;
use Omnistay\Model\PropertyRef;
use Omnistay\Model\Stay;
use Omnistay\Model\Traveller;
use Omnistay\Registry;

/**
 * A stay from quote to payout, for the site's listings:
 *
 *   quote()     what the stay costs, priced now (omnistay);
 *   start()     the nights held for the guest - a pending Booking, under the
 *               store's lock: two guests never hold the same night;
 *   pay()       the guest sent to the gateway (BookingPayments);
 *   complete()  the payment read back: confirmed (or on request when the host
 *               reviews requests), the host's payout scheduled;
 *   respond()   the host accepts or declines a request (declined: refunded);
 *   cancel()    by the guest (the policy's penalty kept) or the host (all
 *               refunded); the nights let go.
 *
 * A hotel of another provider is booked through bookWithCard(): the
 * provider takes the card itself.
 */
final class BookingFlow
{
    public function __construct(
        private readonly Registry $stays,
        private readonly DoctrineListingStore $store,
        private readonly ListingMapper $mapper,
        private readonly BookingRepository $bookings,
        private readonly EntityManagerInterface $entityManager,
        private readonly BookingPayments $payments,
        private readonly Payouts $payouts,
    ) {
    }

    public function quote(PropertyRef $property, Stay $stay, Guests $guests): Availability
    {
        return $this->stays->availabilityOf($property, $stay, $guests);
    }

    /**
     * Holds the nights for the guest: a pending Booking, its hold ending
     * after booking.hold_minutes.
     *
     * @throws UnavailableException when the listing does not take the stay, or another guest took a night meanwhile
     */
    public function start(User $guest, PropertyRef $property, Stay $stay, Guests $guests, ?string $message = null): Booking
    {
        if ($property->provider !== $this->mapper->provider()) {
            throw new BookingException('provider_needs_card');
        }
        $availability = $this->quote($property, $stay, $guests);
        $offer = $availability->cheapest() ?? throw UnavailableException::because($availability->reason ?? 'booked', (string) $property, (string) $stay);
        $this->store->forGuest($guest);
        try {
            $booked = $this->stays->bookOffer(new BookingRequest($offer, [new Traveller($guest->getUserIdentifier(), '', $guest->getEmail())], message: $message));
        } finally {
            $this->store->forGuest(null);
        }
        $booking = $this->bookings->findOneByReference($booked->reference) ?? throw new \LogicException('The store did not keep the booking it held.');
        $booking->setOfferId($offer->id);
        $this->entityManager->flush();

        return $booking;
    }

    /** The address to send the guest to for the payment. */
    public function pay(Booking $booking, string $returnUrl, string $cancelUrl, ?string $locale = null): string
    {
        if (BookingStatus::Pending !== $booking->getStatus() || !$booking->holds(new \DateTimeImmutable())) {
            throw new BookingException('not_payable');
        }

        return $this->payments->begin($booking, $returnUrl, $cancelUrl, $locale);
    }

    /**
     * The payment read back: paid, the booking is confirmed (or on request)
     * and the host's payout scheduled; refused, the nights let go. A
     * booking past its hold whose nights went to someone else meanwhile is
     * cancelled and refunded.
     */
    public function complete(Booking $booking): Booking
    {
        if (BookingStatus::Pending !== $booking->getStatus()) {
            return $booking;
        }
        $state = $this->payments->check($booking);
        if (PaymentState::Failed === $state) {
            $this->local()->cancel($booking->getReference(), 'payment_failed');
            $this->entityManager->refresh($booking);
            $booking->cancel(new \DateTimeImmutable(), 'payment_failed');
        } elseif (PaymentState::Paid === $state) {
            if (!$booking->holds(new \DateTimeImmutable()) && [] !== $this->store->occupied((string) $booking->getListing()?->getId(), $booking->getCheckIn(), $booking->getCheckOut(), new \DateTimeImmutable())) {
                $this->payments->refund($booking, $booking->getTotal(), 'nights_taken');
                $booking->cancel(new \DateTimeImmutable(), 'nights_taken');
            } else {
                $this->local()->confirm($booking->getReference());
                $this->entityManager->refresh($booking);
                $booking->setPaymentState(PaymentState::Paid);
                if (BookingStatus::Confirmed === $booking->getStatus()) {
                    $this->payouts->schedule($booking);
                }
            }
        }
        $this->entityManager->flush();

        return $booking;
    }

    /** The host's answer to a request: accepted, confirmed and the payout scheduled; declined, refunded. */
    public function respond(Booking $booking, User $host, bool $accept): Booking
    {
        if (!$booking->isHost($host)) {
            throw new BookingException('not_yours');
        }
        if (BookingStatus::OnRequest !== $booking->getStatus()) {
            throw new BookingException('not_a_request');
        }
        if ($accept) {
            $booking->setStatus(BookingStatus::Confirmed)->setConfirmedAt(new \DateTimeImmutable());
            $this->payouts->schedule($booking);
        } else {
            $this->payments->refund($booking, $booking->getTotal(), 'declined');
            $booking->cancel(new \DateTimeImmutable(), 'declined')->setStatus(BookingStatus::Refused);
        }
        $this->entityManager->flush();

        return $booking;
    }

    /**
     * Cancelled by its guest - the policy's penalty kept, the rest refunded -
     * or by its host - everything refunded. The nights are free again.
     */
    public function cancel(Booking $booking, User $by, ?string $reason = null, ?\DateTimeImmutable $now = null): Booking
    {
        $now ??= new \DateTimeImmutable();
        $byHost = $booking->isHost($by);
        if (!$byHost && !$booking->isGuest($by)) {
            throw new BookingException('not_yours');
        }
        if (!$booking->getStatus()->holds() || BookingStatus::Completed === $booking->getStatus()) {
            throw new BookingException('not_cancellable');
        }
        $total = Money::of($booking->getTotal(), $booking->getCurrency());
        $penalty = $byHost ? 0 : $this->policy($booking)->penaltyAt($now, $total)->amount;
        $this->payments->refund($booking, $booking->getTotal() - $penalty, $byHost ? 'cancelled_by_host' : 'cancelled_by_guest');
        if ($booking->getProvider() === $this->mapper->provider()) {
            $this->local()->cancel($booking->getReference(), $reason);
            $this->entityManager->refresh($booking);
        } elseif (null !== $booking->getProviderReference()) {
            $this->stays->cancel($booking->getProvider())->cancel($booking->getProviderReference(), $reason);
        }
        $booking->cancel($now, $reason ?? ($byHost ? 'cancelled_by_host' : 'cancelled_by_guest'));
        $this->payouts->cancelFor($booking);
        $this->entityManager->flush();

        return $booking;
    }

    /** The penalty the guest would pay cancelling now. */
    public function penalty(Booking $booking, ?\DateTimeImmutable $now = null): int
    {
        return $this->policy($booking)->penaltyAt($now ?? new \DateTimeImmutable(), Money::of($booking->getTotal(), $booking->getCurrency()))->amount;
    }

    /** Pending bookings past their hold, let go; how many. */
    public function expire(\DateTimeImmutable $now): int
    {
        $expired = $this->bookings->findExpired($now);
        foreach ($expired as $booking) {
            $booking->cancel($now, 'payment_expired');
        }
        $this->entityManager->flush();

        return \count($expired);
    }

    /** A property of another provider - a hotel - booked with the card its provider takes. */
    public function bookWithCard(User $guest, Offer $offer, PaymentCard $card, Traveller $traveller): Booking
    {
        $booked = $this->stays->bookOffer(new BookingRequest($offer, [$traveller], $card));
        $booking = (new Booking($guest))
            ->setProvider($offer->property->provider)
            ->setPropertyRef((string) $offer->property)
            ->setPropertyName($this->stays->propertyOf($offer->property)->name ?? (string) $offer->property)
            ->setProviderReference($booked->reference)
            ->setStay($offer->stay)
            ->setGuests($offer->guests->adults, $offer->guests->childAges, $offer->guests->pets)
            ->setStatus($booked->status)
            ->setAmounts($offer->total()->currency, ($booked->total ?? $offer->total())->amount, 0)
            ->setBreakdown($offer->rate->toArray())
            ->setCancellation($offer->cancellation->toArray())
            ->setOfferId($offer->id);
        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }

    private function local(): LocalProvider
    {
        $provider = $this->stays->get($this->mapper->provider());

        return $provider instanceof LocalProvider ? $provider : throw new \LogicException(\sprintf('The "%s" provider is not omnistay/local.', $this->mapper->provider()));
    }

    private function policy(Booking $booking): CancellationPolicy
    {
        $policy = $booking->getCancellation();
        $freeUntil = \is_string($policy['freeUntil'] ?? null) ? new \DateTimeImmutable($policy['freeUntil']) : null;
        $percent = $policy['penaltyPercent'] ?? null;

        return new CancellationPolicy((bool) ($policy['refundable'] ?? true), $freeUntil, null, \is_int($percent) ? $percent : null);
    }
}
