<?php

namespace Base\Booking\Enum;

/** Where a booking's payment stands, whatever the gateway calls it. */
enum PaymentState: string
{
    case None = 'none';
    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Failed = 'failed';
}
