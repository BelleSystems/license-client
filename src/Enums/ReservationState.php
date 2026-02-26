<?php

namespace Bellesoft\PorticoIptv\Enums;

enum ReservationState: string
{
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
    case PENDING = 'PENDING';
    case EXPIRED = 'EXPIRED';
}