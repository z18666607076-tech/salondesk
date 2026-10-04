<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Actions\RescheduleAppointment;
use App\Exceptions\CancellationWindowException;
use App\Exceptions\SlotUnavailableException;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Appointment) {
            return $record;
        }

        $startsAt = CarbonImmutable::parse((string) $data['starts_at']);

        if (! $record->starts_at->equalTo($startsAt)) {
            /** @var User|null $user */
            $user = auth()->user();

            try {
                $record = app(RescheduleAppointment::class)->handle(
                    $record,
                    $startsAt,
                    $user?->seesEveryAppointment() ?? false,
                );
            } catch (SlotUnavailableException|CancellationWindowException $exception) {
                throw ValidationException::withMessages([
                    'starts_at' => $exception->getMessage(),
                ]);
            }
        }

        $record->forceFill([
            'notes' => $data['notes'] ?? $record->notes,
            'status' => $data['status'] ?? $record->status,
        ])->save();

        return $record->refresh();
    }
}
