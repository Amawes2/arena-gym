<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class DashboardPeriod
{
    private function __construct(public readonly string $value) {}

    public static function fromFilter(mixed $value): self
    {
        return new self(is_string($value) && in_array($value, ['today', 'week', 'month', 'year', 'all'], true) ? $value : 'today');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function bounds(): array
    {
        $end = today()->endOfDay();
        $start = match ($this->value) {
            'week' => today()->startOfWeek(),
            'month' => today()->startOfMonth(),
            'year' => today()->startOfYear(),
            'all' => today()->subYears(10)->startOfDay(),
            default => today()->startOfDay(),
        };

        return [$start, $end];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function buckets(): array
    {
        [$start, $end] = $this->bounds();
        $unit = $this->bucketUnit();
        $cursor = match ($unit) {
            'month' => $start->copy()->startOfMonth(),
            'year' => $start->copy()->startOfYear(),
            default => $start->copy()->startOfDay(),
        };
        $buckets = [];

        while ($cursor->lte($end)) {
            $key = match ($unit) {
                'month' => $cursor->format('Y-m'),
                'year' => $cursor->format('Y'),
                default => $cursor->format('Y-m-d'),
            };
            $label = match ($unit) {
                'month' => $cursor->copy()->locale('fr')->translatedFormat('M Y'),
                'year' => $cursor->format('Y'),
                default => $cursor->format('d/m'),
            };

            $buckets[] = ['key' => $key, 'label' => $label];
            $cursor = match ($unit) {
                'month' => $cursor->addMonth(),
                'year' => $cursor->addYear(),
                default => $cursor->addDay(),
            };
        }

        return $buckets;
    }

    public function keyFor(string $date): string
    {
        $date = Carbon::parse($date);

        return match ($this->bucketUnit()) {
            'month' => $date->format('Y-m'),
            'year' => $date->format('Y'),
            default => $date->format('Y-m-d'),
        };
    }

    private function bucketUnit(): string
    {
        return match ($this->value) {
            'year' => 'month',
            'all' => 'year',
            default => 'day',
        };
    }
}
