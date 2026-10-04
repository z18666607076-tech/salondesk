<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Actions\CancelAppointment;
use App\Enums\AppointmentStatus;
use App\Exceptions\CancellationWindowException;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('staff.name')
                    ->searchable(),
                TextColumn::make('service.name')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('source')
                    ->badge()
                    ->searchable(),
                TextColumn::make('cancelled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cancellation_reason')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('cancel')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $record): bool => $record->status === AppointmentStatus::Confirmed)
                    ->action(function (Appointment $record): void {
                        /** @var User|null $user */
                        $user = auth()->user();

                        try {
                            app(CancelAppointment::class)->handle($record, $user?->hasRole('owner') ?? false);
                        } catch (CancellationWindowException|SlotUnavailableException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
