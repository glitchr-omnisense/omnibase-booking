<?php

namespace Base\Booking\Payment;

use Base\Booking\Entity\Booking;
use Base\Booking\Enum\PaymentState;
use Doctrine\ORM\EntityManagerInterface;
use Omnitrade\Model\Line;
use Omnitrade\Model\Money;
use Omnitrade\Model\Payment;
use Omnitrade\Model\Status;
use Omnitrade\Registry as Gateways;

/**
 * A booking's payment through omnitrade: the guest sent to the gateway's
 * page (Stripe Checkout), the payment read back on return or by its
 * webhook, refunds. booking.payment.gateway names the omnitrade gateway;
 * with none, booking.payment.test lets a booking be "paid" by a button
 * (development and demos: nothing is charged, the gateway is "test"),
 * else no booking can be paid.
 */
final class BookingPayments
{
    public const TEST = 'test';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ?Gateways $gateways = null,
        private readonly ?string $gateway = null,
        private readonly bool $test = false,
    ) {
    }

    /** The gateway a payment goes through now: the configured one, "test", or none. */
    public function gateway(): ?string
    {
        if (null !== $this->gateway && '' !== $this->gateway && null !== $this->gateways && $this->gateways->has($this->gateway)) {
            return $this->gateway;
        }

        return $this->test ? self::TEST : null;
    }

    /**
     * Opens the payment: the address to send the guest to - the gateway's
     * page, or $returnUrl itself for a test payment.
     */
    public function begin(Booking $booking, string $returnUrl, string $cancelUrl, ?string $locale = null): string
    {
        $gateway = $this->gateway() ?? throw new \LogicException('No payment gateway: set booking.payment.gateway (an omnitrade gateway) or booking.payment.test.');
        if (self::TEST === $gateway) {
            $booking->setPayment(self::TEST, 'test_'.$booking->getReference(), PaymentState::Pending);
            $this->entityManager->flush();

            return $returnUrl;
        }
        $amount = Money::of($booking->getTotal(), $booking->getCurrency());
        $label = \sprintf('%s · %s → %s', $booking->getPropertyName(), $booking->getCheckIn()->format('Y-m-d'), $booking->getCheckOut()->format('Y-m-d'));
        $transaction = $this->gateways()->get($gateway)->purchase(new Payment(
            $amount,
            $booking->getReference(),
            $label,
            lines: [new Line($label, $amount)],
            returnUrl: $returnUrl,
            cancelUrl: $cancelUrl,
            idempotencyKey: 'booking-'.$booking->getReference().'-'.$booking->getTotal(),
            metadata: ['booking' => $booking->getReference()],
            locale: $locale,
        ));
        $booking->setPayment($gateway, $transaction->reference, $transaction->isPaid() ? PaymentState::Paid : PaymentState::Pending);
        $this->entityManager->flush();

        return $transaction->redirectUrl ?? $returnUrl;
    }

    /** Where the booking's payment stands now, as the gateway has it (kept on the booking). */
    public function check(Booking $booking): PaymentState
    {
        $gateway = $booking->getPaymentGateway();
        $reference = $booking->getPaymentReference();
        if (null === $gateway || null === $reference) {
            return PaymentState::None;
        }
        if (self::TEST === $gateway) {
            $state = $this->test ? PaymentState::Paid : PaymentState::Failed;
        } else {
            $transaction = $this->gateways()->get($gateway)->fetch($reference);
            $state = match (true) {
                Status::REFUNDED === $transaction->status => PaymentState::Refunded,
                Status::PARTIALLY_REFUNDED === $transaction->status => PaymentState::PartiallyRefunded,
                $transaction->status->isSettled() => PaymentState::Paid,
                \in_array($transaction->status, [Status::REFUSED, Status::CANCELLED, Status::EXPIRED], true) => PaymentState::Failed,
                default => PaymentState::Pending,
            };
        }
        $booking->setPaymentState($state);

        return $state;
    }

    /** Gives $amount (minor units) back to the guest; nothing when nothing was paid. */
    public function refund(Booking $booking, int $amount, string $reason): void
    {
        if ($amount <= 0 || PaymentState::Paid !== $booking->getPaymentState() || null === $booking->getPaymentReference()) {
            return;
        }
        $amount = min($amount, $booking->getTotal() - $booking->getRefunded());
        if (self::TEST !== $booking->getPaymentGateway()) {
            $this->gateways()->get((string) $booking->getPaymentGateway())->refund((string) $booking->getPaymentReference(), Money::of($amount, $booking->getCurrency()), 'booking-refund-'.$booking->getReference().'-'.$amount, $reason);
        }
        $booking->setRefunded($booking->getRefunded() + $amount);
        $booking->setPaymentState($booking->getRefunded() >= $booking->getTotal() ? PaymentState::Refunded : PaymentState::PartiallyRefunded);
    }

    /**
     * What a gateway's webhook says: the booking's reference it is about, read
     * from the payment's metadata; null when it is not a booking's.
     *
     * @param array<string, string|string[]> $headers
     */
    public function notified(string $gateway, string $body, array $headers): ?string
    {
        $notification = $this->gateways()->get($gateway)->notify($body, $headers);
        $reference = $notification->transaction?->metadata['booking'] ?? null;

        return \is_string($reference) ? $reference : null;
    }

    private function gateways(): Gateways
    {
        return $this->gateways ?? throw new \LogicException('omnisense/omnitrade is not installed.');
    }
}
