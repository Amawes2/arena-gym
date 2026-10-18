<?php

namespace App\Filament\Platform\Widgets;

use App\Models\User;
use App\Services\PlatformDashboardMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isSuperAdmin();
    }

    protected function getStats(): array
    {
        if (! static::canView()) {
            return [];
        }

        $metrics = app(PlatformDashboardMetrics::class)->summary();

        return [
            Stat::make('Salles inscrites', number_format($metrics['gyms_total'], 0, ',', ' '))
                ->description($metrics['gyms_new_month'].' nouvelle(s) ce mois')
                ->descriptionIcon(Heroicon::OutlinedBuildingOffice2)
                ->color('primary'),
            Stat::make('Salles actives', number_format($metrics['gyms_active'], 0, ',', ' '))
                ->description('Approuvées et à jour')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make(
                'Taux de salles actives',
                number_format($metrics['gyms_total'] > 0 ? ($metrics['gyms_active'] / $metrics['gyms_total']) * 100 : 0, 1, ',', ' ').'%'
            )
                ->description('Part des salles inscrites')
                ->descriptionIcon(Heroicon::OutlinedChartBar)
                ->color('success'),
            Stat::make('À valider', number_format($metrics['gyms_pending'], 0, ',', ' '))
                ->description('Demandes en attente')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($metrics['gyms_pending'] > 0 ? 'warning' : 'gray'),
            Stat::make('Comptes gestionnaires', number_format($metrics['managers_total'], 0, ',', ' '))
                ->description($metrics['managers_new_month'].' nouveau(x) ce mois')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('info'),
            Stat::make('Membres suivis', number_format($metrics['members_total'], 0, ',', ' '))
                ->description($metrics['members_new_month'].' ajouté(s) ce mois')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color('primary'),
            Stat::make('Abonnements salle expirés', number_format($metrics['gyms_expired'], 0, ',', ' '))
                ->description($metrics['gyms_expiring'].' échéance(s) dans 7 jours')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($metrics['gyms_expired'] > 0 || $metrics['gyms_expiring'] > 0 ? 'danger' : 'gray'),
        ];
    }
}
