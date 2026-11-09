<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\Booking;
use Base\Booking\Entity\Listing;
use Base\Booking\Entity\Review;
use Base\Booking\Enum\ReviewDirection;
use Base\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Review> */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findOneFor(Booking $booking, ReviewDirection $direction): ?Review
    {
        return $this->findOneBy(['booking' => $booking, 'direction' => $direction]);
    }

    /** @return list<Review> the guests' reviews of a listing, newest first */
    public function findForListing(Listing $listing): array
    {
        return $this->findBy(['listing' => $listing, 'direction' => ReviewDirection::GuestToHost, 'hidden' => false], ['createdAt' => 'DESC']);
    }

    /** @return list<Review> what was written about a member, newest first */
    public function findAbout(User $user): array
    {
        return $this->findBy(['subject' => $user, 'hidden' => false], ['createdAt' => 'DESC']);
    }

    /** @return list<Review> what a member wrote, newest first */
    public function findByAuthor(User $user): array
    {
        return $this->findBy(['author' => $user], ['createdAt' => 'DESC']);
    }
}
