<?php

namespace App\Booking;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class CalculateAvailability
{
    /**
     * @return Collection<int, AvailabilitySlot>
     */
    public function forDate(
        Service $service,
        CarbonImmutable $date,
        ?User $staff = null,
        ?string $partOfDay = null,
        ?int $ignoreAppointmentId = null,
    ): Collection {
        $tenant = $service->tenant()->firstOrFail();
        $timezone = $tenant->timezone;
        $localDate = $date->timezone($timezone)->startOfDay();

        $staffMembers = User::query()
            ->where('is_bookable', true)
            ->when($staff !== null, fn ($query) => $query->whereKey($staff->id))
            ->with('schedules')
            ->orderBy('name')
            ->get();

        $slots = new Collection;

        foreach ($staffMembers as $member) {
            $schedule = $member->schedules->firstWhere('weekday', $localDate->dayOfWeek);

            if ($schedule === null) {
                continue;
            }

            $shiftStart = CarbonImmutable::parse(
                $localDate->toDateString().' '.$this->normalizeTime((string) $schedule->starts_time),
                $timezone,
            );
            $shiftEnd = CarbonImmutable::parse(
                $localDate->toDateString().' '.$this->normalizeTime((string) $schedule->ends_time),
                $timezone,
            );
            $cursor = $shiftStart;
            $interval = max(5, (int) $tenant->slot_interval_minutes);
            $duration = (int) $service->duration_minutes;
            $now = CarbonImmutable::now($timezone);

            while ($cursor->addMinutes($duration)->lessThanOrEqualTo($shiftEnd)) {
                $slotEnd = $cursor->addMinutes($duration);

                if (
                    $cursor->greaterThan($now)
                    && $this->matchesPartOfDay($cursor, $partOfDay)
                    && ! $this->overlaps($member, $cursor, $slotEnd, $ignoreAppointmentId)
                ) {
                    $slots->push(new AvailabilitySlot(
                        staffId: $member->id,
                        staffName: $member->name,
                        serviceId: $service->id,
                        startsAt: $cursor->utc(),
                        endsAt: $slotEnd->utc(),
                    ));
                }

                $cursor = $cursor->addMinutes($interval);
            }
        }

        return $slots
            ->sortBy(fn (AvailabilitySlot $slot): int => $slot->startsAt->getTimestamp())
            ->values();
    }

    public function findSlot(
        Service $service,
        User $staff,
        CarbonImmutable $startsAt,
        ?int $ignoreAppointmentId = null,
    ): ?AvailabilitySlot {
        return $this->forDate($service, $startsAt, $staff, null, $ignoreAppointmentId)
            ->first(fn (AvailabilitySlot $slot): bool => $slot->startsAt->equalTo($startsAt->utc()));
    }

    private function overlaps(
        User $staff,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $ignoreAppointmentId,
    ): bool {
        return Appointment::query()
            ->where('staff_id', $staff->id)
            ->whereIn('status', [AppointmentStatus::Confirmed->value, AppointmentStatus::Completed->value])
            ->when($ignoreAppointmentId !== null, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->where('starts_at', '<', $end->utc())
            ->where('ends_at', '>', $start->utc())
            ->exists();
    }

    private function matchesPartOfDay(CarbonImmutable $start, ?string $partOfDay): bool
    {
        if ($partOfDay === null || $partOfDay === '') {
            return true;
        }

        $hour = (int) $start->format('G');

        return match ($partOfDay) {
            'morning' => $hour >= 5 && $hour < 12,
            'afternoon' => $hour >= 12 && $hour < 17,
            'evening' => $hour >= 17 && $hour < 21,
            default => true,
        };
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
