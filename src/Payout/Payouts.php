<?php

namespace Base\Booking\Payout;

use Base\Booking\Entity\Booking;
use Base\Booking\Entity\Payout;
use Base\Booking\Enum\PayoutStatus;
use Base\Booking\Repository\PayoutRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * What hosts are owed: a payout per confirmed booking, the host's share
 * less the host fee (booking.host_fee, in percent), due the day after the
 * check-in - the guest has arrived; release() marks the due ones paid
 * (booking:payouts, from the cron container), cancelFor() drops one with
 * its booking.
 */
final class Payouts
{
    public function __construct(
        private readonly PayoutRepository $payouts,
        private readonly EntityManagerInterface $entityManager,
        private readonly float $hostFee = 3.0,
    ) {
    }

    /** @return array{amount: int, fee: int} what a host share comes to */
    public function split(int $hostShare): array
    {
        $fee = (int) round($hostShare * $this->hostFee / 100);

        return ['amount' => $hostShare - $fee, 'fee' => $fee];
    }

    public function schedule(Booking $booking): ?Payout
    {
        $listing = $booking->getListing();
        if (null === $listing || null !== $this->payouts->findOneBy(['booking' => $booking])) {
            return null;
        }
        ['amount' => $amount, 'fee' => $fee] = $this->split($booking->getHostShare());
        $payout = new Payout($listing->getHost(), $booking, $amount, $fee, $booking->getCurrency(), $booking->getCheckIn()->modify('+1 day'));
        $this->entityManager->persist($payout);

        return $payout;
    }

    public function cancelFor(Booking $booking): void
    {
        $payout = $this->payouts->findOneBy(['booking' => $booking]);
        if (null !== $payout && PayoutStatus::Scheduled === $payout->getStatus()) {
            $payout->cancel();
        }
    }

    /** Marks the payouts due on or before $day paid; how many. */
    public function release(\DateTimeImmutable $day): int
    {
        $due = $this->payouts->findDue($day);
        foreach ($due as $payout) {
            $payout->markPaid(new \DateTimeImmutable(), 'TR-'.$payout->getBooking()->getReference());
        }
        $this->entityManager->flush();

        return \count($due);
    }
}
