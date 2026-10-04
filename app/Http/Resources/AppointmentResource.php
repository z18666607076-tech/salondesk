<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class AppointmentResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'appointments';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        /** @var Appointment $appointment */
        $appointment = $this->resource;

        return [
            'status' => $appointment->status->value,
            'source' => $appointment->source->value,
            'starts_at' => $appointment->starts_at->toIso8601String(),
            'ends_at' => $appointment->ends_at->toIso8601String(),
            'notes' => $appointment->notes,
            'customer_id' => $appointment->customer_id,
            'staff_id' => $appointment->staff_id,
            'service_id' => $appointment->service_id,
            'customer_name' => $appointment->customer->name,
            'staff_name' => $appointment->staff->name,
            'service_name' => $appointment->service->name,
        ];
    }
}
