<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\CalendarNight;
use Base\Booking\Entity\Listing;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/** @extends ServiceEntityRepository<CalendarNight> */
class CalendarNightRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, CalendarNight::class);
    }

    /** @return list<CalendarNight> the nights set apart from $from to $to (excluded), in order */
    public function between(Listing $listing, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        // The nights are bound with the mapping's type (date_immutable), as the hand-written query bound them.
        /** @var list<CalendarNight> */
        return $this->findBy([
            'listing' => Key::of($listing),
            Group::of(Parser::SEPARATOR_AND,
                Criterion::of('night', $from, Parser::OPTION_GREATER_EQUAL),
                Criterion::of('night', $to, Parser::OPTION_LOWER),
            ),
        ], ['night' => 'ASC']);
    }
}
