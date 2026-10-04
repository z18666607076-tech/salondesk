<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('staff_id')
                    ->label('Staff member')
                    ->relationship('staff', 'name', fn ($query) => $query->where('is_bookable', true))
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('service_id')
                    ->relationship('service', 'name', fn ($query) => $query->where('is_active', true))
                    ->required()
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('starts_at')
                    ->required()
                    ->seconds(false),
                DateTimePicker::make('ends_at')
                    ->seconds(false)
                    ->hiddenOn('create')
                    ->required(fn (string $operation): bool => $operation !== 'create'),
                Select::make('status')
                    ->options(AppointmentStatus::class)
                    ->hiddenOn('create')
                    ->required(fn (string $operation): bool => $operation !== 'create'),
                Select::make('source')
                    ->options(AppointmentSource::class)
                    ->hiddenOn('create')
                    ->disabled(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
