<?php

namespace Base\Booking\Enum;

/** Who reviews whom: the guest the host's place, the host the guest. */
enum ReviewDirection: string
{
    case GuestToHost = 'guest_to_host';
    case HostToGuest = 'host_to_guest';
}
