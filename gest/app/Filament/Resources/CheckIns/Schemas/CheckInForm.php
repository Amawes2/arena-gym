<?php

namespace App\Filament\Resources\CheckIns\Schemas;

use App\Enums\CheckInType;
use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\Subscription;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CheckInForm
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

                        Select::make('type')
                            ->label('Type d\'entrée')
                            ->options(collect(CheckInType::cases())->mapWithKeys(
                                fn (CheckInType $t) => [$t->value => $t->label()]
                            ))
                            ->required()
                            ->default(CheckInType::Subscription->value),

                        Select::make('subscription_id')
                            ->label('Abonnement')
                            ->options(fn (Get $get) => Subscription::query()
                                ->where('member_id', $get('member_id'))
                                ->active()
                                ->with('plan')
                                ->get()
                                ->pluck('plan.name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->visible(fn (Get $get) => $get('type') === CheckInType::Subscription->value),

                        TextInput::make('payment_amount')
                            ->label('Montant ticket')
                            ->numeric()
                            ->minValue(0)
                            ->required(fn (Get $get): bool => $get('type') === CheckInType::WalkIn->value)
                            ->visible(fn (Get $get): bool => $get('type') === CheckInType::WalkIn->value),

                        Select::make('payment_method')
                            ->label('Mode de paiement')
                            ->options(collect(PaymentMethod::cases())->mapWithKeys(
                                fn (PaymentMethod $method) => [$method->value => $method->label()]
                            ))
                            ->required(fn (Get $get): bool => $get('type') === CheckInType::WalkIn->value)
                            ->visible(fn (Get $get): bool => $get('type') === CheckInType::WalkIn->value),

                        TextInput::make('notes')
                            ->label('Notes')
                            ->nullable()
                            ->maxLength(255),
                    ]),
            ]);
    }
}
