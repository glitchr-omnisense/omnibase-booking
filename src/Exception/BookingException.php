<?php

namespace Base\Booking\Exception;

/**
 * A booking step refused by the site's rules, its message a translation key
 * of the "booking" domain (booking.error.<reason>): not the guest's booking,
 * a review before the stay ended, a second review, an empty message...
 */
final class BookingException extends \DomainException
{
    public function __construct(public readonly string $reason, ?\Throwable $previous = null)
    {
        parent::__construct('booking.error.'.$reason, 0, $previous);
    }
}
