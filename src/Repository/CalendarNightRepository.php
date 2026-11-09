<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\CalendarNight;
use Base\Booking\Entity\Listing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CalendarNight> */
class CalendarNightRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalendarNight::class);
    }

    /** @return list<CalendarNight> the nights set apart from $from to $to (excluded), in order */
    public function between(Listing $listing, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /** @var list<CalendarNight> */
        return $this->createQueryBuilder('n')
            ->andWhere('n.listing = :listing')->setParameter('listing', $listing)
            ->andWhere('n.night >= :from')->setParameter('from', $from, Types::DATE_IMMUTABLE)
            ->andWhere('n.night < :to')->setParameter('to', $to, Types::DATE_IMMUTABLE)
            ->orderBy('n.night', 'ASC')
            ->getQuery()->getResult();
    }
}
