<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Enums\ClientType;
use App\Enums\PaymentMethod;
use App\Enums\PlanBillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Support\PlanEndDateCalculator;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── Informations du membre ──────────────────────────────
                Section::make('Informations du membre')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
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
                            ->unique(table: 'members', column: 'phone', ignoreRecord: true)
                            ->maxLength(20),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->nullable()
                            ->unique(table: 'members', column: 'email', ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('id_document_number')
                            ->label("N° pièce d'identité")
                            ->nullable()
                            ->maxLength(80),

                        // client_type figé à subscriber dans cette page
                        Select::make('client_type')
                            ->label('Type de client')
                            ->options(collect(ClientType::cases())->mapWithKeys(
                                fn (ClientType $t) => [$t->value => $t->label()]
                            ))
                            ->default(ClientType::Subscriber->value)
                            ->required()
                            ->hidden(),
                    ]),

                // ── Photo ─────────────────────────────────────────────
                Section::make('Photo')
                    ->icon('heroicon-o-camera')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('photo_path')
                            ->label('Photo du membre')
                            ->image()
                            ->directory('members/photos')
                            ->visibility('public')
                            ->nullable()
                            ->columnSpanFull(),

                        Textarea::make('bio')
                            ->label('Notes / Observations')
                            ->nullable()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // ── Abonnement ────────────────────────────────────────
                Section::make('Abonnement')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(2)
                    ->schema([
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

                                $plan = Plan::query()->find($state);
                                if (! $plan) {
                                    return;
                                }

                                $startDate = $get('start_date') ?? now()->toDateString();
                                $set('end_date', PlanEndDateCalculator::endDateFor($plan, $startDate)->toDateString());
                                $set('sessions_remaining', $plan->sessions_limit);
                            })
                            // ── Créer un nouveau forfait depuis le formulaire ──────
                            ->createOptionForm([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nom du forfait')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('price')
                                            ->label('Prix (FCFA)')
                                            ->required()
                                            ->numeric()
                                            ->minValue(0)
                                            ->prefix('FCFA'),

                                        Select::make('billing_period')
                                            ->label('Période')
                                            ->options(collect([
                                                PlanBillingPeriod::Monthly,
                                                PlanBillingPeriod::Quarterly,
                                                PlanBillingPeriod::Annual,
                                            ])->mapWithKeys(
                                                fn (PlanBillingPeriod $p) => [$p->value => $p->label()]
                                            ))
                                            ->required()
                                            ->default(PlanBillingPeriod::Monthly->value)
                                            ->live()
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                                $set('duration_days', match ($state) {
                                                    PlanBillingPeriod::Monthly->value => 30,
                                                    PlanBillingPeriod::Quarterly->value => 90,
                                                    PlanBillingPeriod::Annual->value => 365,
                                                    default => 30,
                                                });
                                            }),

                                        TextInput::make('duration_days')
                                            ->label('Durée (jours)')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(30)
                                            ->disabled()
                                            ->dehydrated(),

                                        TextInput::make('sessions_limit')
                                            ->label('Nb séances (vide = illimité)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->nullable(),

                                        Toggle::make('is_active')
                                            ->label('Actif')
                                            ->default(true)
                                            ->inline(false),
                                    ]),

                                Textarea::make('description')
                                    ->label('Description')
                                    ->nullable()
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                return Plan::create($data)->getKey();
                            })
                            // ── Modifier un forfait existant inline ───────────────
                            ->editOptionForm([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nom du forfait')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('price')
                                            ->label('Prix (FCFA)')
                                            ->required()
                                            ->numeric()
                                            ->minValue(0)
                                            ->prefix('FCFA'),

                                        Select::make('billing_period')
                                            ->label('Période')
                                            ->options(collect([
                                                PlanBillingPeriod::Monthly,
                                                PlanBillingPeriod::Quarterly,
                                                PlanBillingPeriod::Annual,
                                            ])->mapWithKeys(
                                                fn (PlanBillingPeriod $p) => [$p->value => $p->label()]
                                            ))
                                            ->required()
                                            ->default(PlanBillingPeriod::Monthly->value),

                                        TextInput::make('duration_days')
                                            ->label('Durée (jours)')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1),

                                        TextInput::make('sessions_limit')
                                            ->label('Nb séances (vide = illimité)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->nullable(),

                                        Toggle::make('is_active')
                                            ->label('Actif')
                                            ->default(true)
                                            ->inline(false),
                                    ]),

                                Textarea::make('description')
                                    ->label('Description')
                                    ->nullable()
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->updateOptionUsing(function (array $data, int $optionId): void {
                                Plan::find($optionId)?->update($data);
                            }),

                        Select::make('subscription_status')
                            ->label('Statut abonnement')
                            ->options(collect(SubscriptionStatus::cases())->mapWithKeys(
                                fn (SubscriptionStatus $s) => [$s->value => $s->label()]
                            ))
                            ->required()
                            ->default(SubscriptionStatus::Active->value),

                        DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required()
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                $planId = $get('plan_id');
                                if (! $planId || ! $state) {
                                    return;
                                }

                                $plan = Plan::query()->find($planId);
                                if ($plan) {
                                    $set('end_date', PlanEndDateCalculator::endDateFor($plan, $state)->toDateString());
                                }
                            }),

                        DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->required()
                            ->helperText('Calculée automatiquement selon le forfait.'),

                        TextInput::make('sessions_remaining')
                            ->label('Séances restantes (vide = illimité)')
                            ->numeric()
                            ->nullable()
                            ->minValue(0),
                    ]),

                // ── Paiement initial ───────────────────────────────────
                Section::make('Paiement')
                    ->icon('heroicon-o-banknotes')
                    ->columns(2)
                    ->schema([
                        TextInput::make('payment_amount')
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

                        TextInput::make('payment_reference')
                            ->label('Référence (ex: N° transaction Mobile Money)')
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
