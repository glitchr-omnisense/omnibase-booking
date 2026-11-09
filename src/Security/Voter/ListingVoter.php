<?php

namespace Base\Booking\Security\Voter;

use Base\Booking\Entity\Listing;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * A listing is managed - its calendar, its prices, its bookings - by its
 * host.
 *
 * @extends Voter<string, Listing>
 */
final class ListingVoter extends Voter
{
    public const MANAGE = 'LISTING_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::MANAGE === $attribute && $subject instanceof Listing;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return null !== $token->getUser() && $subject->getHost()->getUser() === $token->getUser();
    }
}
