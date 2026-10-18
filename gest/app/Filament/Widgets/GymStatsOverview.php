<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\CheckIn;
use App\Models\Payment;
use App\Models\Subscription;
use App\Support\DashboardPeriod;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GymStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Aujourd’hui en un coup d’œil';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $period = DashboardPeriod::fromFilter($this->filters['period'] ?? null);
        [$startDate, $endDate] = $period->bounds();

        $activeSubscriptionsCount = Subscription::query()
            ->active()
            ->pluck('member_id')
            ->unique()
            ->count();

        $todayCheckIns = CheckIn::query()
            ->whereBetween('checked_in_at', [$startDate, $endDate])
            ->count();

        $expiringSoonCount = Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->whereDate('end_date', '>=', today()->toDateString())
            ->whereDate('end_date', '<=', today()->addDays(7)->toDateString())
            ->count();

        $todayCash = Payment::query()
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('amount');

        return [
            Stat::make('Abonnements actifs', $activeSubscriptionsCount)
                ->description('Accès valide')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color('success'),

            Stat::make('Entrées', $todayCheckIns)
                ->description(match ($period->value) {
                    'week' => 'Sur 7 jours', 'month' => 'Sur le mois', 'year' => 'Sur l\'année', 'all' => 'Total', default => 'Passages enregistrés',
                })
                ->descriptionIcon(Heroicon::OutlinedArrowRightEndOnRectangle)
                ->color('primary'),

            Stat::make('Expirent bientôt', $expiringSoonCount)
                ->description('Dans les 7 prochains jours')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($expiringSoonCount > 0 ? 'warning' : 'gray'),

            Stat::make('Caisse', number_format((float) $todayCash, 0, ',', ' ').' FCFA')
                ->description(match ($period->value) {
                    'week' => 'Encaissements sur 7 jours', 'month' => 'Sur le mois', 'year' => 'Sur l\'année', 'all' => 'Total', default => 'Encaissements du jour',
                })
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success'),
        ];
    }
}
