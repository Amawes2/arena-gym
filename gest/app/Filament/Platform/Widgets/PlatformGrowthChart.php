<?php

namespace App\Filament\Platform\Widgets;

use App\Models\User;
use App\Services\PlatformDashboardMetrics;
use Filament\Widgets\ChartWidget;

class PlatformGrowthChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'Nouvelles salles — 12 derniers mois';

    protected ?string $description = 'Nombre de salles enregistrées chaque mois.';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isSuperAdmin();
    }

    protected function getData(): array
    {
        if (! static::canView()) {
            return ['datasets' => [], 'labels' => []];
        }

        $series = app(PlatformDashboardMetrics::class)->monthlyCounts('gyms');

        return [
            'datasets' => [[
                'label' => 'Salles inscrites',
                'data' => $series['data'],
                'borderColor' => '#f97316',
                'backgroundColor' => 'rgba(249, 115, 22, 0.25)',
                'tension' => 0.35,
                'fill' => true,
            ]],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
