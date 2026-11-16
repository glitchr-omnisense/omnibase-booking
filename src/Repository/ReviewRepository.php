<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\Booking;
use Base\Booking\Entity\Listing;
use Base\Booking\Entity\Review;
use Base\Booking\Enum\ReviewDirection;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;
use Base\Entity\User;

/** @extends ServiceEntityRepository<Review> */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Review::class);
    }

    public function findOneFor(Booking $booking, ReviewDirection $direction): ?Review
    {
        return $this->findOneBy(['booking' => Key::of($booking), 'direction' => $direction]);
    }

    /** @return list<Review> the guests' reviews of a listing, newest first */
    public function findForListing(Listing $listing): array
    {
        return $this->findBy(['listing' => Key::of($listing), 'direction' => ReviewDirection::GuestToHost, 'hidden' => false], ['createdAt' => 'DESC']);
    }

    /** @return list<Review> what was written about a member, newest first */
    public function findAbout(User $user): array
    {
        return $this->findBy(['subject' => Key::of($user), 'hidden' => false], ['createdAt' => 'DESC']);
    }

    /** @return list<Review> what a member wrote, newest first */
    public function findByAuthor(User $user): array
    {
        return $this->findBy(['author' => Key::of($user)], ['createdAt' => 'DESC']);
    }
}
