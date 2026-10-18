<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Enums\ClientType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\CheckInService;
use App\Support\PlanEndDateCalculator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchable(['first_name', 'last_name', 'name', 'phone'])
            ->searchPlaceholder('Rechercher par nom, prénom ou téléphone…')
            ->modifyQueryUsing(
                fn ($query) => $query->with(['activeSubscription.plan'])
            )
            ->columns([
                TextColumn::make('last_name')
                    ->label('Nom')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('first_name')
                    ->label('Prénom')
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->copyable(),

                // Badge type client
                BadgeColumn::make('client_type')
                    ->label('Type')
                    ->formatStateUsing(fn (ClientType $state) => $state->label())
                    ->color(fn (ClientType $state) => match ($state) {
                        ClientType::Subscriber => 'success',
                        ClientType::WalkIn => 'warning',
                    }),

                // Forfait actif — valeur calculée via state() pour éviter l'erreur getRelated()
                TextColumn::make('plan_actif')
                    ->label('Forfait')
                    ->state(fn (Member $record): ?string => $record->activeSubscription?->plan?->name)
                    ->badge()
                    ->color('primary')
                    ->placeholder('—'),

                // Date d'expiration
                TextColumn::make('expiration')
                    ->label('Expiration')
                    ->state(fn (Member $record): ?string => $record->activeSubscription?->end_date?->format('d/m/Y'))
                    ->color(fn (Member $record): string => $record->activeSubscription?->end_date?->lt(today())
                        ? 'danger'
                        : 'success'
                    )
                    ->placeholder('—'),

                // Statut de l'abonnement actif
                TextColumn::make('statut_abonnement')
                    ->label('Statut abo.')
                    ->state(fn (Member $record): ?SubscriptionStatus => $record->activeSubscription?->status)
                    ->badge()
                    ->formatStateUsing(fn (?SubscriptionStatus $state): string => $state?->label() ?? '—')
                    ->color(fn (?SubscriptionStatus $state): string => match ($state) {
                        SubscriptionStatus::Pending => 'warning',
                        SubscriptionStatus::Active => 'success',
                        SubscriptionStatus::Expired => 'danger',
                        SubscriptionStatus::Cancelled => 'gray',
                        null => 'gray',
                    })
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('client_type')
                    ->label('Type de client')
                    ->options(collect(ClientType::cases())->mapWithKeys(
                        fn (ClientType $type) => [$type->value => $type->label()]
                    )),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->recordActions([
                Action::make('check_in')
                    ->label('Pointer')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->color('success')
                    ->visible(fn (Member $record): bool => $record->activeSubscription?->isAccessible() ?? false)
                    ->action(function (Member $record): void {
                        try {
                            app(CheckInService::class)->recordForSubscriber($record);

                            $sub = $record->activeSubscription;
                            $infoMsg = $sub?->sessions_remaining !== null
                                ? "Séances restantes : {$sub->sessions_remaining}"
                                : 'Accès illimité ✅';

                            Notification::make()
                                ->title("Entrée enregistrée — {$record->name}")
                                ->body($infoMsg)
                                ->success()
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('Accès refusé ❌')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('renew')
                    ->label('Renouveler')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
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
                        TextInput::make('end_date_preview')->label('Date de fin calculée')->disabled()->dehydrated(false)->placeholder('Sélectionnez un forfait'),
                        TextInput::make('amount')->label('Montant perçu (FCFA)')->numeric()->required()->minValue(0)->prefix('FCFA'),
                        Select::make('payment_method')->label('Mode de paiement')->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]))->required()->default(PaymentMethod::Cash->value),
                        TextInput::make('reference')->label('Référence paiement')->nullable(),
                    ])
                    ->action(function (array $data, Member $record): void {
                        [$plan, $endDate] = DB::transaction(function () use ($data, $record): array {
                            $plan = Plan::findOrFail($data['plan_id']);
                            $startDate = now()->toDateString();
                            $endDate = PlanEndDateCalculator::endDateFor($plan, $startDate)->toDateString();

                            $record->subscriptions()->where('status', SubscriptionStatus::Active->value)
                                ->update(['status' => SubscriptionStatus::Expired->value]);

                            $subscription = Subscription::create([
                                'member_id' => $record->id,
                                'plan_id' => $plan->id,
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'sessions_remaining' => $plan->sessions_limit,
                                'status' => SubscriptionStatus::Active,
                            ]);

                            Payment::create([
                                'member_id' => $record->id,
                                'subscription_id' => $subscription->id,
                                'amount' => $data['amount'],
                                'payment_method' => $data['payment_method'],
                                'reference' => $data['reference'] ?? null,
                                'status' => PaymentStatus::Completed,
                                'paid_at' => now(),
                            ]);

                            return [$plan, $endDate];
                        });

                        Notification::make()->title('Abonnement renouvelé ✅')->body("Plan : {$plan->name} — jusqu'au ".now()->parse($endDate)->format('d/m/Y'))->success()->send();
                    }),

                ViewAction::make()->label('Profil')->icon('heroicon-o-eye')->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->fetchSelectedRecords(),
                ]),
            ])
            ->emptyStateHeading('Aucun client pour le moment')
            ->emptyStateDescription('Commencez par inscrire votre premier client pour remplir votre base.')
            ->emptyStateIcon('heroicon-o-user-plus')
            ->emptyStateActions([
                Action::make('create_first_client')
                    ->label('Inscrire mon premier client')
                    ->icon('heroicon-o-user-plus')
                    ->url('/admin/subscriptions/create'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
