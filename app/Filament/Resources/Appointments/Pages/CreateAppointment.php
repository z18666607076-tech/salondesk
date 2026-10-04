<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Actions\BookAppointment;
use App\Enums\AppointmentSource;
use App\Exceptions\SlotUnavailableException;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $customer = Customer::query()->findOrFail($data['customer_id']);
        $service = Service::query()->findOrFail($data['service_id']);
        $staff = User::query()->findOrFail($data['staff_id']);

        try {
            return app(BookAppointment::class)->handle(
                service: $service,
                staff: $staff,
                startsAt: CarbonImmutable::parse((string) $data['starts_at']),
                customerName: $customer->name,
                customerEmail: $customer->email,
                customerPhone: $customer->phone,
                source: AppointmentSource::Admin,
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
            );
        } catch (SlotUnavailableException $exception) {
            throw ValidationException::withMessages([
                'starts_at' => $exception->getMessage(),
            ]);
        }
    }
}
