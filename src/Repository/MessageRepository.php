<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\Booking;
use Base\Booking\Entity\Message;
use Base\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Message> */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /** @return list<Message> a booking's thread, oldest first */
    public function thread(Booking $booking): array
    {
        return $this->findBy(['booking' => $booking], ['sentAt' => 'ASC', 'id' => 'ASC']);
    }

    /** How many messages wait for this member across their bookings. */
    public function countUnread(User $user): int
    {
        return (int) $this->createQueryBuilder('m')->select('COUNT(m.id)')
            ->join('m.booking', 'b')->leftJoin('b.listing', 'l')->leftJoin('l.host', 'h')
            ->andWhere('m.readAt IS NULL')->andWhere('m.sender != :user')
            ->andWhere('b.guest = :user OR h.user = :user')->setParameter('user', $user)
            ->getQuery()->getSingleScalarResult();
    }
}
