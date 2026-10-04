<?php

namespace App\Ai;

use App\Ai\Contracts\BookingAssistant;
use App\Booking\AvailabilitySlot;
use App\Booking\CalculateAvailability;
use App\Models\Service;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class FakeBookingAssistant implements BookingAssistant
{
    public function __construct(
        private TenantContext $tenants,
        private CalculateAvailability $availability,
    ) {}

    public function propose(string $message): BookingProposal
    {
        $tenant = $this->tenants->get();

        if ($tenant === null) {
            return new BookingProposal(false, 'No salon is selected.');
        }

        $normalized = mb_strtolower($message);
        $now = CarbonImmutable::now($tenant->timezone);
        $services = Service::query()->where('is_active', true)->get();
        $service = $this->matchService($normalized, $services);

        if ($service === null) {
            $names = $services->pluck('name')->join(', ');

            return new BookingProposal(
                false,
                $names === ''
                    ? 'This salon has no bookable services yet.'
                    : 'Name one of the services: '.$names.'.',
            );
        }

        $staffMembers = User::query()->where('is_bookable', true)->orderBy('name')->get();
        $staff = $this->matchStaff($normalized, $staffMembers);
        $partOfDay = $this->partOfDay($normalized);
        $date = $this->resolveDate($normalized, $now);
        $dates = $date !== null
            ? new Collection([$date])
            : Collection::times(14, fn (int $day): CarbonImmutable => $now->addDays($day - 1)->startOfDay());

        foreach ($dates as $candidate) {
            $slots = $this->availability->forDate($service, $candidate, $staff, $partOfDay);

            if ($staff === null) {
                $slots = $slots->take(1);
            }

            /** @var AvailabilitySlot|null $slot */
            $slot = $slots->first();

            if ($slot !== null) {
                $local = $slot->startsAt->timezone($tenant->timezone);

                return new BookingProposal(
                    available: true,
                    summary: $service->name.' with '.$slot->staffName.' on '.$local->format('D, M j, Y \a\t g:i A').'.',
                    serviceId: $service->id,
                    staffId: $slot->staffId,
                    serviceName: $service->name,
                    staffName: $slot->staffName,
                    startsAt: $slot->startsAt,
                    endsAt: $slot->endsAt,
                );
            }
        }

        return new BookingProposal(
            false,
            'No open '.$service->name.' slot matched that request. Try another day or staff member.',
            serviceId: $service->id,
            serviceName: $service->name,
            staffId: $staff?->id,
            staffName: $staff?->name,
        );
    }

    /**
     * @param  Collection<int, Service>  $services
     */
    private function matchService(string $message, Collection $services): ?Service
    {
        return $services
            ->filter(fn (Service $service): bool => str_contains($message, mb_strtolower($service->name)))
            ->sortByDesc(fn (Service $service): int => mb_strlen($service->name))
            ->first();
    }

    /**
     * @param  Collection<int, User>  $staffMembers
     */
    private function matchStaff(string $message, Collection $staffMembers): ?User
    {
        return $staffMembers
            ->filter(function (User $staff) use ($message): bool {
                $name = mb_strtolower($staff->name);
                $first = mb_strtolower((string) strtok($staff->name, ' '));

                return str_contains($message, $name) || ($first !== '' && preg_match('/\b'.preg_quote($first, '/').'\b/u', $message) === 1);
            })
            ->sortByDesc(fn (User $staff): int => mb_strlen($staff->name))
            ->first();
    }

    private function partOfDay(string $message): ?string
    {
        $parts = [
            'morning' => 'morning',
            '上午' => 'morning',
            '早上' => 'morning',
            'afternoon' => 'afternoon',
            '下午' => 'afternoon',
            'evening' => 'evening',
            '晚上' => 'evening',
        ];

        foreach ($parts as $needle => $part) {
            if (str_contains($message, $needle)) {
                return $part;
            }
        }

        return null;
    }

    private function resolveDate(string $message, CarbonImmutable $now): ?CarbonImmutable
    {
        if (str_contains($message, 'tomorrow') || str_contains($message, '明天')) {
            return $now->addDay()->startOfDay();
        }

        if (preg_match('/\btoday\b/u', $message) === 1 || str_contains($message, '今天')) {
            return $now->startOfDay();
        }

        if (preg_match('/\b(?:next|this|on)?\s*(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/', $message, $matches) === 1) {
            $day = constant(CarbonImmutable::class.'::'.strtoupper($matches[1]));

            return $now->next($day)->startOfDay();
        }

        $weekdays = [
            '星期一' => CarbonImmutable::MONDAY,
            '星期二' => CarbonImmutable::TUESDAY,
            '星期三' => CarbonImmutable::WEDNESDAY,
            '星期四' => CarbonImmutable::THURSDAY,
            '星期五' => CarbonImmutable::FRIDAY,
            '星期六' => CarbonImmutable::SATURDAY,
            '星期日' => CarbonImmutable::SUNDAY,
            '星期天' => CarbonImmutable::SUNDAY,
            '周一' => CarbonImmutable::MONDAY,
            '周二' => CarbonImmutable::TUESDAY,
            '周三' => CarbonImmutable::WEDNESDAY,
            '周四' => CarbonImmutable::THURSDAY,
            '周五' => CarbonImmutable::FRIDAY,
            '周六' => CarbonImmutable::SATURDAY,
            '周日' => CarbonImmutable::SUNDAY,
        ];

        foreach ($weekdays as $label => $day) {
            if (str_contains($message, $label)) {
                return $now->next($day)->startOfDay();
            }
        }

        return null;
    }
}
