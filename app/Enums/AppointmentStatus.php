<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function blocksTime(): bool
    {
        return $this !== self::Cancelled;
    }
}
