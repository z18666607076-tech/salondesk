<?php

namespace App\Http\Resources;

use App\Booking\AvailabilitySlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class SlotResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'slots';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        /** @var AvailabilitySlot $slot */
        $slot = $this->resource;

        return [
            'staff_id' => $slot->staffId,
            'staff_name' => $slot->staffName,
            'service_id' => $slot->serviceId,
            'starts_at' => $slot->startsAt->toIso8601String(),
            'ends_at' => $slot->endsAt->toIso8601String(),
        ];
    }
}
