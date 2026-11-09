<?php

namespace Base\Booking\Security\Voter;

use Base\Booking\Entity\Booking;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may do what with a booking: its guest and its host see it, write in
 * its thread and cancel it; only its host answers a request. The back
 * office goes through the admin's own rules.
 *
 * @extends Voter<string, Booking>
 */
final class BookingVoter extends Voter
{
    public const VIEW = 'BOOKING_VIEW';
    public const MESSAGE = 'BOOKING_MESSAGE';
    public const CANCEL = 'BOOKING_CANCEL';
    public const RESPOND = 'BOOKING_RESPOND';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Booking && \in_array($attribute, [self::VIEW, self::MESSAGE, self::CANCEL, self::RESPOND], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        return match ($attribute) {
            self::RESPOND => $subject->isHost($user),
            default => $subject->isGuest($user) || $subject->isHost($user),
        };
    }
}
