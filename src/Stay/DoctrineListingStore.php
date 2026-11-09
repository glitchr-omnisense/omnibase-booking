<?php

namespace Base\Booking\Stay;

use Base\Booking\Entity\Booking;
use Base\Booking\Entity\Listing;
use Base\Booking\Repository\BookingRepository;
use Base\Booking\Repository\CalendarNightRepository;
use Base\Booking\Repository\ListingRepository;
use Base\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Omnistay\Exception\UnavailableException;
use Omnistay\Local\ListingStoreInterface;
use Omnistay\Local\Quote;
use Omnistay\Local\Reservation;
use Omnistay\Model\BookingStatus;
use Omnistay\Model\CancellationPolicy;
use Omnistay\Model\Guests;
use Omnistay\Model\Money;
use Omnistay\Model\Rate;
use Omnistay\Model\Stay;
use Omnistay\Model\StayQuery;
use Omnistay\Model\Traveller;
use Symfony\Component\Lock\LockFactory;

/**
 * omnistay/local's store on Doctrine: the listings, their calendars, and
 * the Bookings that hold their nights. hold() is where a night is never
 * sold twice: under a lock per listing (symfony/lock - shared by every PHP
 * process when the lock store is), it reads the nights taken and writes the
 * new Booking in one transaction before letting the next guest in.
 *
 * A hold is for a member: forGuest() names them before the provider's
 * book() (BookingFlow does).
 */
final class DoctrineListingStore implements ListingStoreInterface
{
    private ?User $guest = null;

    public function __construct(
        private readonly ListingRepository $listings,
        private readonly CalendarNightRepository $nights,
        private readonly BookingRepository $bookings,
        private readonly EntityManagerInterface $entityManager,
        private readonly LockFactory $locks,
        private readonly ListingMapper $mapper,
    ) {
    }

    /** The member the next hold is for. */
    public function forGuest(?User $guest): self
    {
        $this->guest = $guest;

        return $this;
    }

    public function listings(StayQuery $query): array
    {
        return array_map(fn (Listing $listing) => $this->mapper->toLocal($listing, $this->ownPrices($listing)), $this->listings->findForQuery($query));
    }

    public function listing(string $id): ?\Omnistay\Local\Listing
    {
        $listing = ctype_digit($id) ? $this->listings->find((int) $id) : null;

        return null === $listing ? null : $this->mapper->toLocal($listing, $this->ownPrices($listing));
    }

    public function occupied(string $listing, \DateTimeImmutable $from, \DateTimeImmutable $to, \DateTimeImmutable $now): array
    {
        $entity = ctype_digit($listing) ? $this->listings->find((int) $listing) : null;
        if (null === $entity || $to <= $from) {
            return [];
        }
        $occupied = [];
        foreach ($this->nights->between($entity, $from, $to) as $night) {
            if ($night->isBlocked()) {
                $occupied[$night->getNight()->format('Y-m-d')] = 'blocked';
            }
        }
        $range = new Stay($from, $to);
        foreach ($this->bookings->holding($entity, $from, $to, $now) as $booking) {
            foreach ($booking->getStay()->dates() as $night) {
                if ($range->contains($night)) {
                    $occupied[$night->format('Y-m-d')] = 'booked';
                }
            }
        }
        ksort($occupied);

        return $occupied;
    }

    public function hold(Reservation $reservation, \DateTimeImmutable $now): Reservation
    {
        $guest = $this->guest ?? throw new \LogicException('A hold is for a member: call forGuest() first.');
        $listing = $this->listings->find((int) $reservation->listing) ?? throw new UnavailableException(\sprintf('No listing "%s".', $reservation->listing), 'closed');
        $lock = $this->locks->createLock('omnibase-booking-listing-'.$reservation->listing, 30);
        $lock->acquire(true);
        try {
            $booking = $this->entityManager->wrapInTransaction(function () use ($reservation, $now, $listing, $guest): Booking {
                $taken = $this->occupied($reservation->listing, $reservation->stay->checkIn, $reservation->stay->checkOut, $now);
                if ([] !== $taken) {
                    throw UnavailableException::because((string) reset($taken), $listing->getTitle(), (string) $reservation->stay);
                }
                $booking = (new Booking($guest))
                    ->setListing($listing)
                    ->setProvider($this->mapper->provider())
                    ->setPropertyRef((string) $this->mapper->ref($listing))
                    ->setPropertyName($listing->getTitle())
                    ->setStay($reservation->stay)
                    ->setGuests($reservation->guests->adults, $reservation->guests->childAges, $reservation->guests->pets)
                    ->setStatus($reservation->status)
                    ->setAmounts($reservation->rate->total->currency, $reservation->rate->total->amount, Quote::hostShare($reservation->rate)->amount)
                    ->setBreakdown($reservation->rate->toArray())
                    ->setCancellation($reservation->cancellation->toArray())
                    ->setHoldExpiresAt($reservation->expiresAt)
                    ->setMessage($reservation->message)
                    ->setCreatedAt($now);
                $booking->setProviderReference($booking->getReference());
                if (BookingStatus::Confirmed === $reservation->status) {
                    $booking->setConfirmedAt($now);
                }
                $this->entityManager->persist($booking);
                $this->entityManager->flush();

                return $booking;
            });
        } finally {
            $lock->release();
        }

        return $reservation->with($booking->getReference());
    }

    public function reservation(string $reference): ?Reservation
    {
        $booking = $this->bookings->findOneByReference($reference);
        if (null === $booking || null === $booking->getListing()) {
            return null;
        }
        $guest = $booking->getGuest();

        return new Reservation(
            (string) $booking->getListing()->getId(),
            $booking->getStay(),
            new Guests($booking->getAdults(), $booking->getChildAges(), $booking->getPets()),
            new Rate(Money::of($booking->getTotal(), $booking->getCurrency())),
            $booking->getStatus(),
            self::policy($booking->getCancellation(), $booking->getCurrency()),
            [new Traveller($guest->getUserIdentifier(), '', $guest->getEmail())],
            $booking->getReference(),
            $booking->getReference(),
            $booking->getMessage(),
            $booking->getCreatedAt(),
            $booking->getHoldExpiresAt(),
            $booking->getPaymentReference(),
        );
    }

    public function update(string $reference, BookingStatus $status): ?Reservation
    {
        $booking = $this->bookings->findOneByReference($reference);
        if (null === $booking) {
            return null;
        }
        $now = new \DateTimeImmutable();
        if (BookingStatus::Cancelled === $status) {
            $booking->cancel($now, $booking->getCancelReason());
        } else {
            $booking->setStatus($status)->setHoldExpiresAt(null);
            if (BookingStatus::Confirmed === $status) {
                $booking->setConfirmedAt($now);
            }
        }
        $this->entityManager->flush();

        return $this->reservation($reference);
    }

    /** @return list<\Base\Booking\Entity\CalendarNight> the nights priced on their own, today on */
    private function ownPrices(Listing $listing): array
    {
        $today = Stay::day(new \DateTimeImmutable());

        return array_values(array_filter($this->nights->between($listing, $today, $today->modify('+'.max(1, $listing->getBookingWindow() + 31).' days')), static fn ($n) => null !== $n->getPrice()));
    }

    /** @param array<mixed> $policy CancellationPolicy::toArray() */
    private static function policy(array $policy, string $currency): CancellationPolicy
    {
        $freeUntil = \is_string($policy['freeUntil'] ?? null) ? new \DateTimeImmutable($policy['freeUntil']) : null;
        $percent = $policy['penaltyPercent'] ?? null;

        return new CancellationPolicy((bool) ($policy['refundable'] ?? true), $freeUntil, null, \is_int($percent) ? $percent : null, \is_string($policy['text'] ?? null) ? $policy['text'] : null, \is_string($policy['code'] ?? null) ? $policy['code'] : null);
    }
}
