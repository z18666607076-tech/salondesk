<?php

namespace App\Actions;

use App\Booking\CalculateAvailability;
use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Exceptions\SlotUnavailableException;
use App\Jobs\SendAppointmentConfirmation;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Scopes\TenantScope;
use App\Models\Service;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class BookAppointment
{
    public function __construct(private CalculateAvailability $availability) {}

    public function handle(
        Service $service,
        User $staff,
        CarbonImmutable $startsAt,
        string $customerName,
        string $customerEmail,
        ?string $customerPhone,
        AppointmentSource $source,
        ?string $notes = null,
    ): Appointment {
        return DB::transaction(function () use ($service, $staff, $startsAt, $customerName, $customerEmail, $customerPhone, $source, $notes): Appointment {
            $service = Service::query()->lockForUpdate()->findOrFail($service->id);
            $staff = User::query()->withoutGlobalScope(TenantScope::class)->lockForUpdate()->findOrFail($staff->id);
            $tenant = $service->tenant()->firstOrFail();

            app(TenantContext::class)->set($tenant);

            if ($staff->tenant_id !== $tenant->id || ! $service->is_active || ! $staff->is_bookable) {
                throw new SlotUnavailableException('That service or staff member is not available.');
            }

            $slot = $this->availability->findSlot($service, $staff, $startsAt);

            if ($slot === null) {
                throw new SlotUnavailableException('That time is not an open slot for this staff member.');
            }

            $overlap = Appointment::query()
                ->where('staff_id', $staff->id)
                ->whereIn('status', [AppointmentStatus::Confirmed->value, AppointmentStatus::Completed->value])
                ->where('starts_at', '<', $slot->endsAt)
                ->where('ends_at', '>', $slot->startsAt)
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                throw new SlotUnavailableException('That time was just taken. Choose another slot.');
            }

            $customer = Customer::query()->firstOrCreate(
                ['email' => $customerEmail],
                ['name' => $customerName, 'phone' => $customerPhone],
            );

            $customer->fill([
                'name' => $customerName,
                'phone' => $customerPhone ?? $customer->phone,
            ])->save();

            $appointment = Appointment::query()->create([
                'customer_id' => $customer->id,
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'starts_at' => $slot->startsAt,
                'ends_at' => $slot->endsAt,
                'status' => AppointmentStatus::Confirmed,
                'source' => $source,
                'notes' => $notes,
            ]);

            SendAppointmentConfirmation::dispatch($appointment->id);

            return $appointment->load(['customer', 'staff', 'service']);
        });
    }
}
