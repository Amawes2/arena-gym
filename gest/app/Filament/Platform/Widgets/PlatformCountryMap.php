<?php

namespace App\Filament\Platform\Widgets;

use App\Models\User;
use App\Services\PlatformDashboardMetrics;
use Filament\Widgets\Widget;

class PlatformCountryMap extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.platform.platform-country-map';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isSuperAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        if (! static::canView()) {
            return [
                'countries' => [],
                'mapCountries' => [],
                'totalGyms' => 0,
                'unmappedGyms' => 0,
            ];
        }

        $distribution = app(PlatformDashboardMetrics::class)->countryDistribution();

        return [
            'countries' => $distribution['countries'],
            'mapCountries' => array_values(array_filter(
                $distribution['countries'],
                fn (array $country): bool => $country['coordinates'] !== null,
            )),
            'totalGyms' => $distribution['total_gyms'],
            'unmappedGyms' => $distribution['unmapped_gyms'],
        ];
    }
}
