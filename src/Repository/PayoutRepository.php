<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\HostProfile;
use Base\Booking\Entity\Payout;
use Base\Booking\Enum\PayoutStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Payout> */
class PayoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payout::class);
    }

    /** @return list<Payout> */
    public function findForHost(HostProfile $host): array
    {
        return $this->findBy(['host' => $host], ['dueOn' => 'DESC']);
    }

    /** @return list<Payout> the scheduled payouts due on or before $day */
    public function findDue(\DateTimeImmutable $day): array
    {
        /** @var list<Payout> */
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :scheduled')->setParameter('scheduled', PayoutStatus::Scheduled->value)
            ->andWhere('p.dueOn <= :day')->setParameter('day', $day, Types::DATE_IMMUTABLE)
            ->getQuery()->getResult();
    }
}
