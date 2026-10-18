<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\DashboardPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Revenus encaisses';

    protected ?string $description = 'Abonnements et seances uniques sur la periode choisie.';

    protected function getData(): array
    {
        $period = DashboardPeriod::fromFilter($this->filters['period'] ?? null);
        [$start, $end] = $period->bounds();
        $buckets = collect($period->buckets())
            ->mapWithKeys(fn (array $bucket): array => [$bucket['key'] => [
                'label' => $bucket['label'],
                'subscription' => 0.0,
                'walk_in' => 0.0,
            ]])
            ->all();
        $subscriptionExpression = 'CASE WHEN subscription_id IS NULL THEN 0 ELSE 1 END';
        $payments = Payment::query()
            ->where('status', PaymentStatus::Completed->value)
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw("DATE(paid_at) as bucket_date, {$subscriptionExpression} as has_subscription, SUM(amount) as total")
            ->groupByRaw("DATE(paid_at), {$subscriptionExpression}")
            ->get();

        foreach ($payments as $payment) {
            $key = $period->keyFor((string) $payment->bucket_date);

            if (! array_key_exists($key, $buckets)) {
                continue;
            }

            $series = (int) $payment->has_subscription === 1 ? 'subscription' : 'walk_in';
            $buckets[$key][$series] = (float) $payment->total;
        }

        return [
            'labels' => array_column($buckets, 'label'),
            'datasets' => [
                [
                    'label' => 'Revenus abonnements',
                    'data' => array_column($buckets, 'subscription'),
                ],
                [
                    'label' => 'Revenus seances uniques',
                    'data' => array_column($buckets, 'walk_in'),
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
