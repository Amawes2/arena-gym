<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Enums\ClientType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\PlanEndDateCalculator;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderHeading(): string
    {
        return 'Inscrire un client';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
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

                TextInput::make('id_document_number')
                    ->label('N° de pièce')
                    ->maxLength(80)
                    ->nullable(),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->nullable(),

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
                        $set('payment_amount', $plan->price);
                    }),

                DatePicker::make('start_date')
                    ->label('Date début')
                    ->required()
                    ->default(now()->toDateString())
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
                    ->label('Date fin')
                    ->required(),

                TextInput::make('sessions_remaining')
                    ->label('Séances restantes')
                    ->numeric()
                    ->nullable()
                    ->helperText('Laisser vide pour accès illimité.'),

                Select::make('status')
                    ->label('Statut abonnement')
                    ->options(collect(SubscriptionStatus::cases())->mapWithKeys(
                        fn (SubscriptionStatus $status): array => [$status->value => $status->label()]
                    ))
                    ->required()
                    ->default(SubscriptionStatus::Active->value),

                TextInput::make('payment_amount')
                    ->label('Montant perçu (FCFA)')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),

                Select::make('payment_method')
                    ->label('Mode de paiement')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(
                        fn (PaymentMethod $method): array => [$method->value => $method->label()]
                    ))
                    ->required()
                    ->default(PaymentMethod::Cash->value),
            ]),
        ]);
    }

    /**
     * Crée le membre, son abonnement et le paiement initial dans une transaction.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Member {
            // 1. Créer le membre
            $member = Member::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'id_document_number' => $data['id_document_number'] ?? null,
                'photo_path' => $data['photo_path'] ?? null,
                'bio' => $data['bio'] ?? null,
                'client_type' => ClientType::Subscriber,
            ]);

            // 2. Créer l'abonnement si un plan est sélectionné
            $subscription = null;

            if (! empty($data['plan_id'])) {
                $subscription = Subscription::create([
                    'member_id' => $member->id,
                    'plan_id' => $data['plan_id'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'sessions_remaining' => $data['sessions_remaining'] ?? null,
                    'status' => $data['status'],
                ]);
            }

            // 3. Créer le paiement initial si montant renseigné
            $amount = (float) ($data['payment_amount'] ?? 0);
            if ($amount > 0) {
                Payment::create([
                    'member_id' => $member->id,
                    'subscription_id' => $subscription?->id,
                    'amount' => $amount,
                    'payment_method' => $data['payment_method'] ?? PaymentMethod::Cash->value,
                    'reference' => null,
                    'status' => PaymentStatus::Completed,
                    'paid_at' => now(),
                ]);
            }

            return $member;
        });
    }

    protected function getRedirectUrl(): string
    {
        return SubscriptionResource::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        $record = $this->getRecord();

        return Notification::make()
            ->title('Abonné enregistré ✅')
            ->body("Le membre {$record->first_name} {$record->last_name} a été ajouté avec son abonnement.")
            ->success();
    }
}
