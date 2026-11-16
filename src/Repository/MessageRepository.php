<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\Booking;
use Base\Booking\Entity\Message;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;
use Base\Entity\User;

/** @extends ServiceEntityRepository<Message> */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Message::class);
    }

    /** @return list<Message> a booking's thread, oldest first */
    public function thread(Booking $booking): array
    {
        return $this->findBy(['booking' => Key::of($booking)], ['sentAt' => 'ASC', 'id' => 'ASC']);
    }

    /** How many messages wait for this member across their bookings: as the guest, or as the listing's host. */
    public function countUnread(User $user): int
    {
        return $this->count([
            Group::of(Parser::SEPARATOR_AND,
                Criterion::of('readAt', null, Parser::OPTION_NULL),
                Criterion::of('sender', Key::of($user), Parser::OPTION_NOT_EQUAL),
            ),
            Group::of(Parser::SEPARATOR_OR,
                Criterion::of('booking.guest', $user),
                Criterion::of('booking.listing.host.user', $user),
            ),
        ]);
    }
}
