<?php

namespace App\Filament\Platform\Resources\Gyms;

use App\Filament\Platform\Resources\Gyms\Pages\CreateGym;
use App\Filament\Platform\Resources\Gyms\Pages\EditGym;
use App\Filament\Platform\Resources\Gyms\Pages\ListGyms;
use App\Filament\Platform\Resources\Gyms\Schemas\GymForm;
use App\Filament\Platform\Resources\Gyms\Tables\GymsTable;
use App\Models\Gym;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GymResource extends Resource
{
    protected static ?string $model = Gym::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Salles';

    protected static ?string $modelLabel = 'salle';

    protected static ?string $pluralModelLabel = 'salles';

    public static function form(Schema $schema): Schema
    {
        return GymForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GymsTable::configure($table);
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
            'index' => ListGyms::route('/'),
            'create' => CreateGym::route('/create'),
            'edit' => EditGym::route('/{record}/edit'),
        ];
    }
}
