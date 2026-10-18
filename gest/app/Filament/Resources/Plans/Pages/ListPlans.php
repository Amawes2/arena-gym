<?php

namespace App\Filament\Resources\Plans\Pages;

use App\Filament\Resources\Plans\PlanResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderHeading(): string
    {
        return 'Plans tarifaires';
    }

    protected function getHeaderActions(): array
    {
        return [
            // Retour vers Abonnements
            Action::make('back_to_subscriptions')
                ->label('← Abonnements')
                ->color('gray')
                ->url(SubscriptionResource::getUrl('index')),

            CreateAction::make()
                ->label('Nouveau forfait')
                ->icon('heroicon-o-plus'),
        ];
    }
}
