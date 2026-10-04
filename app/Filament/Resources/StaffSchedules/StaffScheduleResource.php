<?php

namespace App\Filament\Resources\StaffSchedules;

use App\Filament\Resources\StaffSchedules\Pages\CreateStaffSchedule;
use App\Filament\Resources\StaffSchedules\Pages\EditStaffSchedule;
use App\Filament\Resources\StaffSchedules\Pages\ListStaffSchedules;
use App\Filament\Resources\StaffSchedules\Schemas\StaffScheduleForm;
use App\Filament\Resources\StaffSchedules\Tables\StaffSchedulesTable;
use App\Models\StaffSchedule;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffScheduleResource extends Resource
{
    protected static ?string $model = StaffSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Schedules';

    protected static ?string $modelLabel = 'schedule';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return StaffScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffSchedulesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user instanceof User && ! $user->seesEveryAppointment()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffSchedules::route('/'),
            'create' => CreateStaffSchedule::route('/create'),
            'edit' => EditStaffSchedule::route('/{record}/edit'),
        ];
    }
}
