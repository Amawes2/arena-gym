<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GymStatsOverview;
use App\Filament\Widgets\QuickCheckInWidget;
use App\Filament\Widgets\RecentTransactions;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\SubscriptionAlerts;
use App\Filament\Widgets\WalkInsChart;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Tableau de bord limité aux actions utiles à l’accueil (pointage rapide + chiffres clés).
 */
class HomeDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Accueil';

    protected static ?string $navigationLabel = 'Accueil';

    protected static string|UnitEnum|null $navigationGroup = 'Principal';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            QuickCheckInWidget::class,
            GymStatsOverview::class,
            RevenueChart::class,
            WalkInsChart::class,
            SubscriptionAlerts::class,
            RecentTransactions::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('period')
                    ->label('Filtrer par période')
                    ->options([
                        'today' => 'Aujourd\'hui',
                        'week' => 'Cette semaine',
                        'month' => 'Ce mois',
                        'year' => 'Cette année',
                        'all' => 'Depuis le début',
                    ])
                    ->default('today')
                    ->native(false),
            ])
            ->columns(3);
    }
}
