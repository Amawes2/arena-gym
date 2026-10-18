<?php

namespace App\Filament\Widgets;

use App\Enums\CheckInType;
use App\Models\CheckIn;
use App\Support\DashboardPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class WalkInsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Frequentation - seances uniques';

    protected ?string $description = 'Entrees en seance unique sur la periode choisie.';

    protected function getData(): array
    {
        $period = DashboardPeriod::fromFilter($this->filters['period'] ?? null);
        [$start, $end] = $period->bounds();
        $buckets = collect($period->buckets())
            ->mapWithKeys(fn (array $bucket): array => [$bucket['key'] => [
                'label' => $bucket['label'],
                'count' => 0,
            ]])
            ->all();
        $checkIns = CheckIn::query()
            ->where('type', CheckInType::WalkIn->value)
            ->whereBetween('checked_in_at', [$start, $end])
            ->selectRaw('DATE(checked_in_at) as bucket_date, COUNT(id) as total')
            ->groupByRaw('DATE(checked_in_at)')
            ->get();

        foreach ($checkIns as $checkIn) {
            $key = $period->keyFor((string) $checkIn->bucket_date);

            if (array_key_exists($key, $buckets)) {
                $buckets[$key]['count'] = (int) $checkIn->total;
            }
        }

        return [
            'labels' => array_column($buckets, 'label'),
            'datasets' => [
                [
                    'label' => 'Seances uniques',
                    'data' => array_column($buckets, 'count'),
                    'fill' => 'start',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
