<?php

namespace App\Livewire;

use App\Actions\BookAppointment;
use App\Ai\Contracts\BookingAssistant;
use App\Booking\AvailabilitySlot;
use App\Booking\CalculateAvailability;
use App\Enums\AppointmentSource;
use App\Exceptions\SlotUnavailableException;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Support\SalonPreferences;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Component;

class PublicBooking extends Component
{
    public Tenant $tenant;

    public ?int $serviceId = null;

    public ?int $staffId = null;

    public string $date = '';

    public string $startsAt = '';

    public string $customerName = '';

    public string $customerEmail = '';

    public string $customerPhone = '';

    public string $notes = '';

    public string $assistantMessage = '';

    public bool $booked = false;

    public string $confirmation = '';

    /**
     * @var array{available: bool, summary: string, service_id: int|null, staff_id: int|null, service_name: string|null, staff_name: string|null, starts_at: string|null, ends_at: string|null}|null
     */
    public ?array $proposal = null;

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->bindSalon();
        $this->date = $this->initialDate();

        $serviceId = Service::query()->where('is_active', true)->orderBy('id')->value('id');
        $this->serviceId = is_numeric($serviceId) ? (int) $serviceId : null;
    }

    public function boot(): void
    {
        $this->bindSalon();
    }

    public function updatedServiceId(): void
    {
        $this->startsAt = '';
        $this->staffId = null;
        $this->proposal = null;
        $this->resetErrorBag();
    }

    public function updatedDate(): void
    {
        $this->startsAt = '';
        $this->proposal = null;
    }

    public function chooseStaff(string $staffId): void
    {
        $this->staffId = $staffId !== '' ? (int) $staffId : null;
        $this->startsAt = '';
    }

    public function selectSlot(int $staffId, string $startsAt): void
    {
        $this->staffId = $staffId;
        $this->startsAt = $startsAt;
        $this->resetErrorBag('startsAt');
    }

    public function book(BookAppointment $bookAppointment): void
    {
        $this->bindSalon();

        $this->validate([
            'serviceId' => ['required', 'integer'],
            'staffId' => ['required', 'integer'],
            'startsAt' => ['required', 'date'],
            'customerName' => ['required', 'string', 'max:255'],
            'customerEmail' => ['required', 'email', 'max:255'],
            'customerPhone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->storeBooking(
            $bookAppointment,
            (int) $this->serviceId,
            (int) $this->staffId,
            $this->startsAt,
            AppointmentSource::Web,
        );
    }

    public function askAssistant(BookingAssistant $assistant): void
    {
        $this->bindSalon();

        if (! $this->assistantEnabled()) {
            $this->addError('assistantMessage', __('booking.assistant_plan'));

            return;
        }

        $this->validate([
            'assistantMessage' => ['required', 'string', 'max:500'],
        ]);

        $proposal = $assistant->propose($this->assistantMessage);

        $this->proposal = [
            'available' => $proposal->available,
            'summary' => $proposal->summary,
            'service_id' => $proposal->serviceId,
            'staff_id' => $proposal->staffId,
            'service_name' => $proposal->serviceName,
            'staff_name' => $proposal->staffName,
            'starts_at' => $proposal->startsAt?->toIso8601String(),
            'ends_at' => $proposal->endsAt?->toIso8601String(),
        ];
    }

    public function confirmAssistant(BookAppointment $bookAppointment): void
    {
        $this->bindSalon();

        if (! $this->assistantEnabled()) {
            $this->addError('assistantMessage', __('booking.assistant_plan'));

            return;
        }

        $proposal = $this->proposal;

        if ($proposal === null || $proposal['available'] !== true || $proposal['service_id'] === null || $proposal['staff_id'] === null || $proposal['starts_at'] === null) {
            return;
        }

        $this->validate([
            'customerName' => ['required', 'string', 'max:255'],
            'customerEmail' => ['required', 'email', 'max:255'],
            'customerPhone' => ['nullable', 'string', 'max:32'],
        ]);

        $this->serviceId = $proposal['service_id'];
        $this->staffId = $proposal['staff_id'];
        $this->startsAt = $proposal['starts_at'];

        $this->storeBooking(
            $bookAppointment,
            $proposal['service_id'],
            $proposal['staff_id'],
            $proposal['starts_at'],
            AppointmentSource::Ai,
        );
    }

    public function bookAnother(): void
    {
        $this->booked = false;
        $this->confirmation = '';
        $this->startsAt = '';
        $this->proposal = null;
        $this->notes = '';
    }

    public function assistantEnabled(): bool
    {
        return isset($this->tenant) && $this->tenant->resolvedPlan()->allowsAssistant();
    }

    public function render(): View
    {
        $this->bindSalon();

        return view('livewire.public-booking', [
            'services' => $this->services(),
            'staffMembers' => $this->staffMembers(),
            'openings' => $this->slots(),
        ])->layout('layouts.booking', [
            'salonName' => $this->tenant->name,
            'htmlLang' => SalonPreferences::locale($this->tenant) === 'zh_CN' ? 'zh-CN' : 'en',
        ]);
    }

    /**
     * @return Collection<int, Service>
     */
    private function services(): Collection
    {
        return Service::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function staffMembers(): Collection
    {
        return User::query()->where('is_bookable', true)->orderBy('name')->get();
    }

    /**
     * @return SupportCollection<int, AvailabilitySlot>
     */
    private function slots(): SupportCollection
    {
        if ($this->serviceId === null || $this->date === '') {
            return new SupportCollection;
        }

        $service = Service::query()->where('is_active', true)->find($this->serviceId);

        if ($service === null) {
            return new SupportCollection;
        }

        $staff = $this->staffId !== null
            ? User::query()->where('is_bookable', true)->find($this->staffId)
            : null;

        return app(CalculateAvailability::class)->forDate(
            $service,
            CarbonImmutable::parse($this->date, $this->tenant->timezone),
            $staff,
        );
    }

    private function storeBooking(
        BookAppointment $bookAppointment,
        int $serviceId,
        int $staffId,
        string $startsAt,
        AppointmentSource $source,
    ): void {
        $service = Service::query()->where('is_active', true)->find($serviceId);
        $staff = User::query()->where('is_bookable', true)->find($staffId);

        if ($service === null || $staff === null) {
            $this->addError('startsAt', __('booking.no_slots'));

            return;
        }

        try {
            $appointment = $bookAppointment->handle(
                service: $service,
                staff: $staff,
                startsAt: CarbonImmutable::parse($startsAt),
                customerName: $this->customerName,
                customerEmail: $this->customerEmail,
                customerPhone: $this->customerPhone !== '' ? $this->customerPhone : null,
                source: $source,
                notes: $this->notes !== '' ? $this->notes : null,
            );
        } catch (SlotUnavailableException $exception) {
            $this->addError('startsAt', $exception->getMessage());

            return;
        }

        $when = $appointment->starts_at
            ->timezone($this->tenant->timezone)
            ->format('D, M j, Y \a\t g:i A');

        $this->booked = true;
        $this->confirmation = __('booking.booked_body', [
            'service' => $appointment->service->name,
            'staff' => $appointment->staff->name,
            'when' => $when.' ('.$this->tenant->timezone.')',
        ]);
    }

    private function bindSalon(): void
    {
        if (! isset($this->tenant)) {
            return;
        }

        app(TenantContext::class)->set($this->tenant);
        SalonPreferences::apply($this->tenant);
    }

    private function initialDate(): string
    {
        $cursor = CarbonImmutable::now($this->tenant->timezone)->addDay()->startOfDay();

        for ($day = 0; $day < 14; $day++) {
            if ($cursor->dayOfWeek !== CarbonImmutable::SUNDAY) {
                return $cursor->toDateString();
            }

            $cursor = $cursor->addDay();
        }

        return CarbonImmutable::now($this->tenant->timezone)->toDateString();
    }
}
