<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\HostProfile;
use Base\Booking\Entity\Payout;
use Base\Booking\Enum\PayoutStatus;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/** @extends ServiceEntityRepository<Payout> */
class PayoutRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Payout::class);
    }

    /** @return list<Payout> */
    public function findForHost(HostProfile $host): array
    {
        return $this->findBy(['host' => Key::of($host)], ['dueOn' => 'DESC']);
    }

    /** @return list<Payout> the scheduled payouts due on or before $day */
    public function findDue(\DateTimeImmutable $day): array
    {
        // The day is bound with the mapping's type (date_immutable), as the hand-written query bound it.
        /** @var list<Payout> */
        return $this->findBy([
            'status' => PayoutStatus::Scheduled->value,
            Group::of(Parser::SEPARATOR_AND, Criterion::of('dueOn', $day, Parser::OPTION_LOWER_EQUAL)),
        ]);
    }
}
