<?php

namespace App\Filament\Resources\CheckIns;

use App\Filament\Resources\CheckIns\Pages\CreateCheckIn;
use App\Filament\Resources\CheckIns\Pages\EditCheckIn;
use App\Filament\Resources\CheckIns\Pages\ListCheckIns;
use App\Filament\Resources\CheckIns\Schemas\CheckInForm;
use App\Filament\Resources\CheckIns\Tables\CheckInsTable;
use App\Models\CheckIn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CheckInResource extends Resource
{
    protected static ?string $model = CheckIn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowRightEndOnRectangle;

    protected static ?string $navigationLabel = 'Entrées / Check-in';

    protected static string|UnitEnum|null $navigationGroup = 'Accueil';

    protected static ?int $navigationSort = 1;

    /**
     * Pointage depuis la liste Clients ou l’accueil ; masqué pour alléger le menu.
     */
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return CheckInForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckInsTable::configure($table);
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
            'index' => ListCheckIns::route('/'),
            'create' => CreateCheckIn::route('/create'),
            'edit' => EditCheckIn::route('/{record}/edit'),
        ];
    }
}
