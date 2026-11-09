<?php

namespace Base\Booking\Calendar;

use Base\Booking\Entity\CalendarNight;
use Base\Booking\Entity\Listing;
use Base\Booking\Repository\CalendarNightRepository;
use Base\Booking\Stay\ListingMapper;
use Doctrine\ORM\EntityManagerInterface;
use Omnistay\Model\CalendarDay;
use Omnistay\Model\Stay;
use Omnistay\Registry;

/**
 * A host's calendar: each night of a month as guests see it (free, booked,
 * blocked, its price, the minimum stay from it - omnistay/local's
 * calendar), and the host's hand on it: block or open nights, price nights
 * on their own. A booked night is left as it is: blocking it would change
 * nothing for the guest who holds it.
 */
final class HostCalendar
{
    public function __construct(
        private readonly Registry $stays,
        private readonly ListingMapper $mapper,
        private readonly CalendarNightRepository $nights,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @return list<CalendarDay> the nights of the month that $day falls in */
    public function month(Listing $listing, \DateTimeInterface $day): array
    {
        $first = Stay::day($day)->modify('first day of this month');

        return $this->stays->calendar($this->mapper->provider())->calendar($this->mapper->ref($listing), $first, $first->modify('first day of next month'));
    }

    /** @return list<CalendarDay> */
    public function between(Listing $listing, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->stays->calendar($this->mapper->provider())->calendar($this->mapper->ref($listing), $from, $to);
    }

    /**
     * Blocks the nights from the first to the last of $nights (its check-out
     * excluded), or opens them; the booked ones are left.
     *
     * @return array{changed: int, booked: list<string>}
     */
    public function block(Listing $listing, Stay $nights, bool $blocked = true, ?string $note = null): array
    {
        $booked = [];
        $changed = 0;
        $days = $this->between($listing, $nights->checkIn, $nights->checkOut);
        foreach ($days as $day) {
            if ('booked' === $day->reason) {
                $booked[] = $day->date->format('Y-m-d');
                continue;
            }
            $night = $this->night($listing, $day->date);
            if ($night->isBlocked() !== $blocked) {
                ++$changed;
            }
            $night->setBlocked($blocked)->setNote($blocked ? $note : null);
            $this->tidy($night);
        }
        $this->entityManager->flush();

        return ['changed' => $changed, 'booked' => $booked];
    }

    /** Prices the nights on their own (minor units), or gives them back to the listing's prices (null). */
    public function price(Listing $listing, Stay $nights, ?int $price): int
    {
        $count = 0;
        foreach ($nights->dates() as $date) {
            $this->tidy($this->night($listing, $date)->setPrice(null === $price ? null : max(0, $price)));
            ++$count;
        }
        $this->entityManager->flush();

        return $count;
    }

    private function night(Listing $listing, \DateTimeImmutable $date): CalendarNight
    {
        $night = $this->nights->findOneBy(['listing' => $listing, 'night' => $date]);

        return $night ?? new CalendarNight($listing, $date);
    }

    private function tidy(CalendarNight $night): void
    {
        if ($night->isEmpty() && null !== $night->getId()) {
            $this->entityManager->remove($night);
        } elseif (!$night->isEmpty() && null === $night->getId()) {
            $this->entityManager->persist($night);
        }
    }
}
