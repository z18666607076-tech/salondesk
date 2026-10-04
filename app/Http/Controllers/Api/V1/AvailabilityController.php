<?php

namespace App\Http\Controllers\Api\V1;

use App\Booking\CalculateAvailability;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\AvailabilityRequest;
use App\Http\Resources\SlotResource;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AvailabilityController extends Controller
{
    /**
     * List open slots for a service on a given date.
     */
    public function index(AvailabilityRequest $request, CalculateAvailability $availability): AnonymousResourceCollection
    {
        $service = Service::query()->where('is_active', true)->findOrFail($request->integer('service_id'));
        $staff = $request->filled('staff_id')
            ? User::query()->where('is_bookable', true)->findOrFail($request->integer('staff_id'))
            : null;

        $slots = $availability->forDate(
            $service,
            CarbonImmutable::parse($request->string('date')->toString()),
            $staff,
            $request->filled('time_of_day') ? $request->string('time_of_day')->toString() : null,
        );

        return SlotResource::collection($slots);
    }
}
