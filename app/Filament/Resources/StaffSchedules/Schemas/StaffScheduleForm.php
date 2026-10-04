<?php

namespace App\Filament\Resources\StaffSchedules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class StaffScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Staff member')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('weekday')
                    ->options([
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                    ])
                    ->required(),
                TimePicker::make('starts_time')
                    ->required()
                    ->seconds(false),
                TimePicker::make('ends_time')
                    ->required()
                    ->seconds(false)
                    ->after('starts_time'),
            ]);
    }
}
