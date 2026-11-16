<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\Booking;
use Base\Booking\Entity\HostProfile;
use Base\Booking\Entity\Listing;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;
use Base\Entity\User;
use Omnistay\Model\BookingStatus;

/** @extends ServiceEntityRepository<Booking> */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Booking::class);
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
        // The days are bound with the mapping's type (date_immutable), as the hand-written query bound them.
        /** @var list<Booking> $bookings */
        $bookings = $this->findBy([
            'listing' => Key::of($listing),
            Group::of(Parser::SEPARATOR_AND,
                Criterion::of('checkIn', $to, Parser::OPTION_LOWER),
                Criterion::of('checkOut', $from, Parser::OPTION_GREATER),
            ),
            'status' => [BookingStatus::Pending->value, BookingStatus::Confirmed->value, BookingStatus::OnRequest->value, BookingStatus::Completed->value],
        ]);

        return array_values(array_filter($bookings, static fn (Booking $b) => $b->holds($now)));
    }

    /** @return list<Booking> a guest's bookings, the next stay first */
    public function findForGuest(User $guest): array
    {
        return $this->findBy(['guest' => Key::of($guest)], ['checkIn' => 'DESC']);
    }

    /** @return list<Booking> the bookings of a host's listings, the next stay first */
    public function findForHost(HostProfile $host): array
    {
        /** @var list<Booking> */
        return $this->findBy(['listing.host' => $host], ['checkIn' => 'DESC']);
    }

    /** @return list<Booking> pending bookings whose hold has ended */
    public function findExpired(\DateTimeImmutable $now): array
    {
        /** @var list<Booking> */
        return $this->findBy([
            'status' => BookingStatus::Pending->value,
            Group::of(Parser::SEPARATOR_AND, Criterion::of('holdExpiresAt', $now, Parser::OPTION_LOWER_EQUAL)),
        ]);
    }

    /**
     * The confirmed stays whose check-out is on or before $today marked
     * completed, in one UPDATE (no entity loaded): the daily round's.
     *
     * @return int the bookings completed
     */
    public function completeStays(\DateTimeImmutable $today): int
    {
        return $this->updateBy([
            'status' => BookingStatus::Confirmed->value,
            Group::of(Parser::SEPARATOR_AND, Criterion::of('checkOut', $today, Parser::OPTION_LOWER_EQUAL)),
        ], ['status' => BookingStatus::Completed->value]);
    }
}
