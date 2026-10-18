<?php

namespace App\Providers\Filament;

use App\Filament\Platform\Resources\Gyms\GymResource;
use App\Filament\Platform\Widgets\PlatformCountryMap;
use App\Filament\Platform\Widgets\PlatformGrowthChart;
use App\Filament\Platform\Widgets\PlatformMemberGrowthChart;
use App\Filament\Platform\Widgets\PlatformStatsOverview;
use App\Filament\Resources\SupportMessages\SupportMessageResource;
use App\Http\Middleware\AuthenticateFilamentPanel;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PlatformPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('platform')
            ->path('super-admin')
            ->login()
            ->passwordReset()
            ->profile()
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->brandName('ARENA GYM')
                    ->recoverable(),
            ])
            ->brandName('ARENA GYM - Super Admin')
            ->brandLogo(asset('images/arena-icon-512.png'))
            ->brandLogoHeight('2.5rem')
            ->viteTheme('resources/css/filament/super-admin/theme.css')
            ->colors(['primary' => Color::Orange])
            ->strictAuthorization()
            ->resources([GymResource::class, SupportMessageResource::class])
            ->pages([Dashboard::class])
            ->widgets([
                PlatformStatsOverview::class,
                PlatformGrowthChart::class,
                PlatformMemberGrowthChart::class,
                PlatformCountryMap::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateFilamentPanel::class,
            ]);
    }
}
