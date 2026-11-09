<?php

namespace Base\Booking\Review;

use Base\Booking\Entity\Booking;
use Base\Booking\Entity\Review;
use Base\Booking\Enum\ReviewDirection;
use Base\Booking\Exception\BookingException;
use Base\Booking\Repository\ReviewRepository;
use Base\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Omnistay\Model\BookingStatus;
use Omnistay\Model\Stay;

/**
 * Reviews in both directions, once the stay is over: the guest reviews the
 * host's place, the host the guest; one each per booking, within
 * booking.review_window days of the check-out. Neither is shown before the
 * other is written or the window has closed, so neither answers the other.
 * A guest's review moves the listing's average.
 */
final class Reviews
{
    public function __construct(
        private readonly ReviewRepository $reviews,
        private readonly EntityManagerInterface $entityManager,
        private readonly int $window = 14,
    ) {
    }

    /** The direction this member may still review in for this booking, null when none. */
    public function canWrite(Booking $booking, ?User $user, \DateTimeImmutable $now): ?ReviewDirection
    {
        $direction = $this->direction($booking, $user);
        if (null === $direction || !\in_array($booking->getStatus(), [BookingStatus::Confirmed, BookingStatus::Completed], true)) {
            return null;
        }
        $today = Stay::day($now);
        if ($booking->getCheckOut() > $today || $booking->getCheckOut()->modify(\sprintf('+%d days', $this->window)) < $today) {
            return null;
        }

        return null === $this->reviews->findOneFor($booking, $direction) ? $direction : null;
    }

    public function write(Booking $booking, User $author, int $rating, string $comment, \DateTimeImmutable $now): Review
    {
        $direction = $this->direction($booking, $author) ?? throw new BookingException('not_yours');
        if (null !== $this->reviews->findOneFor($booking, $direction)) {
            throw new BookingException('review_twice');
        }
        if (null === $this->canWrite($booking, $author, $now)) {
            throw new BookingException('review_not_open');
        }
        if (mb_strlen(trim($comment)) < 10) {
            throw new BookingException('review_too_short');
        }
        $subject = ReviewDirection::GuestToHost === $direction ? $booking->getHostUser() : $booking->getGuest();
        if (null === $subject) {
            throw new BookingException('not_yours');
        }
        $review = (new Review($booking, $direction, $author, $subject, $rating, $comment))->setCreatedAt($now);
        $this->entityManager->persist($review);
        if (ReviewDirection::GuestToHost === $direction && null !== $listing = $booking->getListing()) {
            $ratings = array_map(static fn (Review $r) => $r->getRating(), [...$this->reviews->findForListing($listing), $review]);
            $listing->setRating(array_sum($ratings) / \count($ratings), \count($ratings));
        }
        $this->entityManager->flush();

        return $review;
    }

    /** Whether a review is shown: the other side has written too, or the window has closed. */
    public function isVisible(Review $review, \DateTimeImmutable $now): bool
    {
        if ($review->isHidden()) {
            return false;
        }
        $other = ReviewDirection::GuestToHost === $review->getDirection() ? ReviewDirection::HostToGuest : ReviewDirection::GuestToHost;

        return null !== $this->reviews->findOneFor($review->getBooking(), $other)
            || $review->getBooking()->getCheckOut()->modify(\sprintf('+%d days', $this->window)) < Stay::day($now);
    }

    /** @return list<Review> the guests' reviews of a listing that are shown */
    public function forListing(\Base\Booking\Entity\Listing $listing, \DateTimeImmutable $now): array
    {
        return array_values(array_filter($this->reviews->findForListing($listing), fn (Review $r) => $this->isVisible($r, $now)));
    }

    private function direction(Booking $booking, ?User $user): ?ReviewDirection
    {
        return match (true) {
            $booking->isGuest($user) => ReviewDirection::GuestToHost,
            $booking->isHost($user) => ReviewDirection::HostToGuest,
            default => null,
        };
    }
}
