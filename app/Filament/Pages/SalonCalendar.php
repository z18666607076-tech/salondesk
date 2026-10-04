<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\SalonPreferences;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;

class SalonCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'calendar';

    protected string $view = 'filament.pages.salon-calendar';

    public string $mode = 'day';

    public string $date = '';

    public function mount(): void
    {
        $tenant = $this->salon();
        SalonPreferences::apply($tenant);

        $requested = request()->query('view', 'day');
        $this->mode = in_array($requested, ['day', 'week'], true) ? $requested : 'day';

        $date = request()->query('date');
        $this->date = is_string($date) && $date !== ''
            ? CarbonImmutable::parse($date, $tenant->timezone)->toDateString()
            : CarbonImmutable::now($tenant->timezone)->toDateString();
    }

    public function boot(): void
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Tenant) {
            SalonPreferences::apply($tenant);
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('appointments.view');
    }

    public static function getNavigationLabel(): string
    {
        return __('calendar.navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return __('calendar.title');
    }

    public function show(string $mode): void
    {
        if (in_array($mode, ['day', 'week'], true)) {
            $this->mode = $mode;
        }
    }

    public function today(): void
    {
        $this->date = CarbonImmutable::now($this->salon()->timezone)->toDateString();
    }

    public function shift(int $step): void
    {
        $date = CarbonImmutable::parse($this->date, $this->salon()->timezone);
        $this->date = ($this->mode === 'week' ? $date->addWeeks($step) : $date->addDays($step))->toDateString();
    }

    public function heading(): string
    {
        $timezone = $this->salon()->timezone;
        $date = CarbonImmutable::parse($this->date, $timezone);
        $locale = app()->getLocale() === 'zh_CN' ? 'zh_CN' : 'en';

        if ($this->mode === 'week') {
            $start = $date->startOfWeek(CarbonImmutable::MONDAY)->locale($locale);
            $end = $start->endOfWeek(CarbonImmutable::SUNDAY)->locale($locale);

            return $start->translatedFormat('M j').' – '.$end->translatedFormat('M j, Y');
        }

        return $date->locale($locale)->translatedFormat('l, M j, Y');
    }

    /**
     * @return Collection<int, User>
     */
    public function staffColumns(): Collection
    {
        $user = auth()->user();

        return User::query()
            ->where('is_bookable', true)
            ->when(
                $user instanceof User && ! $user->seesEveryAppointment(),
                fn ($query) => $query->whereKey($user->id),
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<array{label: string, cells: list<list<array{customer: string, service: string, time: string}>>}>
     */
    public function dayRows(): array
    {
        $tenant = $this->salon();
        $columns = $this->staffColumns();
        $start = CarbonImmutable::parse($this->date, $tenant->timezone)->startOfDay();
        $appointments = $this->appointmentsBetween($start, $start->endOfDay());
        $rows = [];

        foreach (range(9, 20) as $hour) {
            $cells = [];

            foreach ($columns as $column) {
                $visits = [];

                foreach ($appointments as $appointment) {
                    if ($appointment->staff_id !== $column->id) {
                        continue;
                    }

                    $local = $appointment->starts_at->timezone($tenant->timezone);

                    if ((int) $local->format('G') !== $hour) {
                        continue;
                    }

                    $visits[] = [
                        'customer' => (string) $appointment->customer?->name,
                        'service' => (string) $appointment->service?->name,
                        'time' => $local->format('g:i A'),
                    ];
                }

                $cells[] = $visits;
            }

            $rows[] = [
                'label' => CarbonImmutable::parse($this->date, $tenant->timezone)->setTime($hour, 0)->format('g A'),
                'cells' => $cells,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, visits: list<array{customer: string, service: string, staff: string, time: string}>}>
     */
    public function weekDays(): array
    {
        $tenant = $this->salon();
        $locale = app()->getLocale() === 'zh_CN' ? 'zh_CN' : 'en';
        $start = CarbonImmutable::parse($this->date, $tenant->timezone)->startOfWeek(CarbonImmutable::MONDAY);
        $appointments = $this->appointmentsBetween($start, $start->endOfWeek(CarbonImmutable::SUNDAY));
        $days = [];

        foreach (range(0, 6) as $offset) {
            $day = $start->addDays($offset);
            $visits = [];

            foreach ($appointments as $appointment) {
                $local = $appointment->starts_at->timezone($tenant->timezone);

                if ($local->toDateString() !== $day->toDateString()) {
                    continue;
                }

                $visits[] = [
                    'customer' => (string) $appointment->customer?->name,
                    'service' => (string) $appointment->service?->name,
                    'staff' => (string) $appointment->staff?->name,
                    'time' => $local->format('g:i A'),
                ];
            }

            $days[] = [
                'label' => $day->locale($locale)->translatedFormat('D j'),
                'visits' => $visits,
            ];
        }

        return $days;
    }

    /**
     * @return Collection<int, Appointment>
     */
    private function appointmentsBetween(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $user = auth()->user();

        return Appointment::query()
            ->with(['customer', 'staff', 'service'])
            ->whereIn('status', [AppointmentStatus::Confirmed->value, AppointmentStatus::Completed->value])
            ->where('starts_at', '<', $end->utc())
            ->where('ends_at', '>', $start->utc())
            ->when(
                $user instanceof User && ! $user->seesEveryAppointment(),
                fn ($query) => $query->where('staff_id', $user->id),
            )
            ->orderBy('starts_at')
            ->get();
    }

    private function salon(): Tenant
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Tenant) {
            abort(404);
        }

        return $tenant;
    }

    public function getSubheading(): string|Htmlable|null
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Tenant) {
            return null;
        }

        return $tenant->name.' · '.$tenant->timezone;
    }
}
