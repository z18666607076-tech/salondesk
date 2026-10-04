<?php

namespace App\Ai\Tools;

use App\Booking\AvailabilitySlot;
use App\Booking\CalculateAvailability;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchSlotsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Search open appointment slots. Returns starts_at values that are safe to propose.';
    }

    public function handle(Request $request): Stringable|string
    {
        $service = Service::query()->whereKey((int) $request['service_id'])->first();

        if ($service === null) {
            return '[]';
        }

        $staff = isset($request['staff_id'])
            ? User::query()->whereKey((int) $request['staff_id'])->first()
            : null;

        $date = CarbonImmutable::parse((string) $request['date']);
        $partOfDay = isset($request['time_of_day']) ? (string) $request['time_of_day'] : null;

        $slots = app(CalculateAvailability::class)
            ->forDate($service, $date, $staff, $partOfDay)
            ->take(8)
            ->map(fn (AvailabilitySlot $slot): array => [
                'service_id' => $slot->serviceId,
                'staff_id' => $slot->staffId,
                'staff_name' => $slot->staffName,
                'starts_at' => $slot->startsAt->toIso8601String(),
                'ends_at' => $slot->endsAt->toIso8601String(),
            ])
            ->values();

        return (string) $slots->toJson();
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'service_id' => $schema->integer()->required(),
            'date' => $schema->string()->required(),
            'staff_id' => $schema->integer(),
            'time_of_day' => $schema->string(),
        ];
    }
}
