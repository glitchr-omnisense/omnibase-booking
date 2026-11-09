<?php

namespace Base\Booking\Enum;

/** Where a host's payout stands. */
enum PayoutStatus: string
{
    /** Due the day after the check-in. */
    case Scheduled = 'scheduled';
    case Paid = 'paid';
    /** The booking was cancelled before it was due. */
    case Cancelled = 'cancelled';
}
