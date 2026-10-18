<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\PlanEndDateCalculator;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\DB;

class EditMemberSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderHeading(): string
    {
        $record = $this->getRecord();

        return "Fiche de {$record->first_name} {$record->last_name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            // ── Voir le profil complet ──────────────────────────────────
            Action::make('view_profile')
                ->label('Voir profil')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => SubscriptionResource::getUrl('view', ['record' => $this->getRecord()])),

            // ── Renouveler l'abonnement ─────────────────────────────────
            Action::make('renew')
                ->label('Renouveler')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->modalHeading('Renouveler l\'abonnement')
                ->modalDescription('Crée un nouvel abonnement à partir d\'aujourd\'hui et enregistre le paiement.')
                ->modalWidth('lg')
                ->form([
                    Select::make('plan_id')
                        ->label('Forfait')
                        ->options(Plan::query()->active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                            if (! $state) {
                                return;
                            }
                            $plan = Plan::find($state);
                            if ($plan) {
                                $set('amount', $plan->price);
                                $end = PlanEndDateCalculator::endDateFor($plan, now()->toDateString());
                                $set('end_date_preview', $end->format('d/m/Y'));
                            }
                        }),

                    TextInput::make('end_date_preview')
                        ->label('Date de fin calculée')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Sélectionnez un forfait'),

                    TextInput::make('amount')
                        ->label('Montant perçu (FCFA)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('FCFA'),

                    Select::make('payment_method')
                        ->label('Mode de paiement')
                        ->options(collect(PaymentMethod::cases())->mapWithKeys(
                            fn (PaymentMethod $m) => [$m->value => $m->label()]
                        ))
                        ->required()
                        ->default(PaymentMethod::Cash->value),

                    TextInput::make('reference')
                        ->label('Référence paiement')
                        ->nullable(),
                ])
                ->modalSubmitActionLabel('Confirmer le renouvellement')
                ->action(function (array $data): void {
                    [$plan, $endDate] = DB::transaction(function () use ($data): array {
                        $member = $this->getRecord();
                        $plan = Plan::findOrFail($data['plan_id']);
                        $startDate = now()->toDateString();
                        $endDate = PlanEndDateCalculator::endDateFor($plan, $startDate)->toDateString();

                        $member->subscriptions()
                            ->where('status', SubscriptionStatus::Active->value)
                            ->update(['status' => SubscriptionStatus::Expired->value]);

                        $subscription = Subscription::create([
                            'member_id' => $member->id,
                            'plan_id' => $plan->id,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'sessions_remaining' => $plan->sessions_limit,
                            'status' => SubscriptionStatus::Active,
                        ]);

                        Payment::create([
                            'member_id' => $member->id,
                            'subscription_id' => $subscription->id,
                            'amount' => $data['amount'],
                            'payment_method' => $data['payment_method'],
                            'reference' => $data['reference'] ?? null,
                            'status' => PaymentStatus::Completed,
                            'paid_at' => now(),
                        ]);

                        return [$plan, $endDate];
                    });

                    Notification::make()
                        ->title('Abonnement renouvelé ✅')
                        ->body("Plan : {$plan->name} — jusqu'au ".now()->parse($endDate)->format('d/m/Y'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['id']);
                }),

            // ── Supprimer ───────────────────────────────────────────────
            DeleteAction::make()
                ->label('Supprimer le membre'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return SubscriptionResource::getUrl('index');
    }
}
