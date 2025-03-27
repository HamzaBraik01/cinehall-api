<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Pending = 'Pending';
    case Paid = 'Paid';
    case Cancelled = 'Cancelled';
    case Expired = 'Expired';
}