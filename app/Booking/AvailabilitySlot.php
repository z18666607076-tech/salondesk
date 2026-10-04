<?php

namespace App\Booking;

use Carbon\CarbonImmutable;

final class AvailabilitySlot
{
    public function __construct(
        public int $staffId,
        public string $staffName,
        public int $serviceId,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
    ) {}

    public function getKey(): string
    {
        return $this->staffId.'-'.$this->startsAt->getTimestamp();
    }
}
