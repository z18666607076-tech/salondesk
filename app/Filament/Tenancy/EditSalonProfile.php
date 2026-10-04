<?php

namespace App\Filament\Tenancy;

use App\Support\SalonPreferences;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditSalonProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Salon profile';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('timezone')
                    ->options(SalonPreferences::options(SalonPreferences::TIMEZONES))
                    ->searchable()
                    ->required(),
                Select::make('currency')
                    ->options(SalonPreferences::options(SalonPreferences::CURRENCIES))
                    ->searchable()
                    ->required(),
                Select::make('locale')
                    ->options(SalonPreferences::LOCALES)
                    ->required(),
                TextInput::make('slot_interval_minutes')
                    ->numeric()
                    ->required()
                    ->minValue(5)
                    ->maxValue(120),
                TextInput::make('cancellation_window_hours')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(168),
                TextInput::make('billing_email')
                    ->email()
                    ->maxLength(255),
            ]);
    }
}
