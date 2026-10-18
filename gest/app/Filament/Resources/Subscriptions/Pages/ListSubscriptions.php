<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Enums\PaymentMethod;
use App\Filament\Resources\Plans\PlanResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tarifs')
                ->label('Tarifs')
                ->icon('heroicon-o-tag')
                ->color('gray')
                ->url(PlanResource::getUrl('index'))
                ->tooltip('Modifier les forfaits et les prix'),

            CreateAction::make()
                ->label('Inscrire un client')
                ->icon('heroicon-o-user-plus'),

            Action::make('walk_in')
                ->label('Séance du jour')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->modalHeading('Client sans abonnement')
                ->modalDescription('Paiement d’une seule séance à l’entrée.')
                ->modalWidth('md')
                ->form([
                    TextInput::make('last_name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(120),

                    TextInput::make('first_name')
                        ->label('Prénom')
                        ->required()
                        ->maxLength(120),

                    TextInput::make('phone')
                        ->label('Téléphone')
                        ->tel()
                        ->required()
                        ->maxLength(20),

                    TextInput::make('amount')
                        ->label('Montant (FCFA)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->default(function (): float {
                            $gyms = auth()->user()?->gyms();
                            $gym = $gyms?->first() ?? auth()->user()?->managedGyms()->first();

                            return (float) ($gym?->walk_in_price ?? 0);
                        })
                        ->prefix('FCFA')
                        ->helperText('Tarif pour les séances uniques.'),

                    Select::make('payment_method')
                        ->label('Mode de paiement')
                        ->options(collect(PaymentMethod::cases())->mapWithKeys(
                            fn (PaymentMethod $m) => [$m->value => $m->label()]
                        ))
                        ->required()
                        ->default(PaymentMethod::Cash->value),
                ])
                ->modalSubmitActionLabel('Enregistrer l\'entrée')
                ->action(function (array $data): void {
                    $member = SubscriptionResource::registerWalkIn($data);

                    Notification::make()
                        ->title('Entrée enregistrée ✅')
                        ->body("Séance unique pour {$member->first_name} {$member->last_name} enregistrée.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
