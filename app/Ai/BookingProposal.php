<?php

namespace App\Ai;

use Carbon\CarbonImmutable;

final class BookingProposal
{
    public function __construct(
        public bool $available,
        public string $summary,
        public ?int $serviceId = null,
        public ?int $staffId = null,
        public ?string $serviceName = null,
        public ?string $staffName = null,
        public ?CarbonImmutable $startsAt = null,
        public ?CarbonImmutable $endsAt = null,
    ) {}
}
