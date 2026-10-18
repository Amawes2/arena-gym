<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Subscription;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('member_id')
                            ->label('Membre')
                            ->options(Member::query()->orderBy('last_name')->orderBy('first_name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live(),

                        Select::make('subscription_id')
                            ->label('Abonnement (optionnel)')
                            ->options(fn (Get $get) => Subscription::query()
                                ->where('member_id', $get('member_id'))
                                ->with('plan')
                                ->get()
                                ->pluck('plan.name', 'id'))
                            ->searchable()
                            ->nullable(),

                        TextInput::make('amount')
                            ->label('Montant (FCFA)')
                            ->required()
                            ->numeric()
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
                            ->label('Référence (ex: numéro transaction Mobile Money)')
                            ->nullable()
                            ->maxLength(255),

                        Select::make('status')
                            ->label('Statut')
                            ->options(collect(PaymentStatus::cases())->mapWithKeys(
                                fn (PaymentStatus $s) => [$s->value => $s->label()]
                            ))
                            ->required()
                            ->default(PaymentStatus::Completed->value),

                        DateTimePicker::make('paid_at')
                            ->label('Date/heure du paiement')
                            ->visible(fn (Get $get): bool => $get('status') === PaymentStatus::Completed->value)
                            ->default(now())
                            ->required(fn (Get $get): bool => $get('status') === PaymentStatus::Completed->value),
                    ]),
            ]);
    }
}
