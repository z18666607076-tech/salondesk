<?php

namespace App\Filament\Tenancy;

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
                TextInput::make('timezone')
                    ->required()
                    ->maxLength(64),
                TextInput::make('currency')
                    ->required()
                    ->minLength(3)
                    ->maxLength(3),
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
