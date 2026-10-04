<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255),
                Select::make('role')
                    ->options([
                        'owner' => 'Owner',
                        'receptionist' => 'Receptionist',
                        'staff' => 'Staff',
                    ])
                    ->default('staff')
                    ->required()
                    ->hiddenOn('edit')
                    ->dehydrated(false),
                Toggle::make('is_bookable')
                    ->label('Can take appointments')
                    ->helperText('Turn this off for a receptionist who only runs the desk.')
                    ->default(true),
            ]);
    }
}
