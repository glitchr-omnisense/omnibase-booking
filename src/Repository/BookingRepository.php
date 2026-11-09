<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\Booking;
use Base\Booking\Entity\HostProfile;
use Base\Booking\Entity\Listing;
use Base\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Omnistay\Model\BookingStatus;

/** @extends ServiceEntityRepository<Booking> */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findOneByReference(string $reference): ?Booking
    {
        return $this->findOneBy(['reference' => $reference]);
    }

    /**
     * The bookings that hold a listing's nights between $from and $to
     * (excluded) at this moment: confirmed, on request, completed, or
     * pending within their hold.
     *
     * @return list<Booking>
     */
    public function holding(Listing $listing, \DateTimeImmutable $from, \DateTimeImmutable $to, \DateTimeImmutable $now): array
    {
        /** @var list<Booking> $bookings */
        $bookings = $this->createQueryBuilder('b')
            ->andWhere('b.listing = :listing')->setParameter('listing', $listing)
            ->andWhere('b.checkIn < :to')->setParameter('to', $to, Types::DATE_IMMUTABLE)
            ->andWhere('b.checkOut > :from')->setParameter('from', $from, Types::DATE_IMMUTABLE)
            ->andWhere('b.status IN (:statuses)')->setParameter('statuses', [BookingStatus::Pending->value, BookingStatus::Confirmed->value, BookingStatus::OnRequest->value, BookingStatus::Completed->value])
            ->getQuery()->getResult();

        return array_values(array_filter($bookings, static fn (Booking $b) => $b->holds($now)));
    }

    /** @return list<Booking> a guest's bookings, the next stay first */
    public function findForGuest(User $guest): array
    {
        return $this->findBy(['guest' => $guest], ['checkIn' => 'DESC']);
    }

    /** @return list<Booking> the bookings of a host's listings, the next stay first */
    public function findForHost(HostProfile $host): array
    {
        /** @var list<Booking> */
        return $this->createQueryBuilder('b')->join('b.listing', 'l')
            ->andWhere('l.host = :host')->setParameter('host', $host)
            ->orderBy('b.checkIn', 'DESC')->getQuery()->getResult();
    }

    /** @return list<Booking> pending bookings whose hold has ended */
    public function findExpired(\DateTimeImmutable $now): array
    {
        /** @var list<Booking> */
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :pending')->setParameter('pending', BookingStatus::Pending->value)
            ->andWhere('b.holdExpiresAt <= :now')->setParameter('now', $now)
            ->getQuery()->getResult();
    }
}
