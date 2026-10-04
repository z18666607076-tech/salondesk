<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('duration_minutes')
                    ->required()
                    ->numeric()
                    ->minValue(5)
                    ->suffix('min'),
                TextInput::make('price_cents')
                    ->label('Price (cents)')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                TextInput::make('currency')
                    ->required()
                    ->minLength(3)
                    ->maxLength(3)
                    ->default(fn (): string => Filament::getTenant()->currency ?? 'SGD'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
