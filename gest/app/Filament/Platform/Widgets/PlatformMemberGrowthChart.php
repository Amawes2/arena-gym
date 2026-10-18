<?php

namespace App\Filament\Platform\Widgets;

use App\Models\User;
use App\Services\PlatformDashboardMetrics;
use Filament\Widgets\ChartWidget;

class PlatformMemberGrowthChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'Activité des comptes — 12 derniers mois';

    protected ?string $description = 'Nouveaux comptes gestionnaires et membres ajoutés aux salles.';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isSuperAdmin();
    }

    protected function getData(): array
    {
        if (! static::canView()) {
            return ['datasets' => [], 'labels' => []];
        }

        $metrics = app(PlatformDashboardMetrics::class);
        $managers = $metrics->monthlyCounts('managers');
        $members = $metrics->monthlyCounts('members');

        return [
            'datasets' => [
                [
                    'label' => 'Comptes gestionnaires',
                    'data' => $managers['data'],
                    'borderColor' => '#38bdf8',
                    'backgroundColor' => 'rgba(56, 189, 248, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Membres',
                    'data' => $members['data'],
                    'borderColor' => '#34d399',
                    'backgroundColor' => 'rgba(52, 211, 153, 0.12)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $managers['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
