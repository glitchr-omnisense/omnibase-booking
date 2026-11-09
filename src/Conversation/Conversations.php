<?php

namespace Base\Booking\Conversation;

use Base\Booking\Entity\Booking;
use Base\Booking\Entity\Message;
use Base\Booking\Exception\BookingException;
use Base\Booking\Repository\MessageRepository;
use Base\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The thread of a booking: plain messages between its guest and its host,
 * from the request to after the stay. Reading it marks the other side's
 * messages read.
 */
final class Conversations
{
    public const MAX_LENGTH = 4000;

    public function __construct(private readonly MessageRepository $messages, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function post(Booking $booking, User $sender, string $body): Message
    {
        if (!$booking->isGuest($sender) && !$booking->isHost($sender)) {
            throw new BookingException('not_yours');
        }
        $body = trim($body);
        if ('' === $body) {
            throw new BookingException('message_empty');
        }
        if (mb_strlen($body) > self::MAX_LENGTH) {
            throw new BookingException('message_too_long');
        }
        $message = new Message($booking, $sender, $body);
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    /** @return list<Message> the thread, the other side's messages marked read */
    public function read(Booking $booking, User $reader): array
    {
        if (!$booking->isGuest($reader) && !$booking->isHost($reader)) {
            throw new BookingException('not_yours');
        }
        $thread = $this->messages->thread($booking);
        $now = new \DateTimeImmutable();
        foreach ($thread as $message) {
            if ($message->getSender() !== $reader) {
                $message->markRead($now);
            }
        }
        $this->entityManager->flush();

        return $thread;
    }

    public function unread(User $user): int
    {
        return $this->messages->countUnread($user);
    }
}
