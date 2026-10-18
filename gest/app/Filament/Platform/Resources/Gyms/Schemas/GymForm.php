<?php

namespace App\Filament\Platform\Resources\Gyms\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class GymForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom de la salle')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('Identifiant URL')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('owner_id')
                            ->label('Compte responsable')
                            ->options(fn (): array => User::query()
                                ->whereIn('role', [UserRole::Admin->value, UserRole::SuperAdmin->value])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),

                        Select::make('plan_saas')
                            ->label('Formule Arena')
                            ->options([
                                'trial' => 'Essai',
                                'basic' => 'Basic',
                                'pro' => 'Pro',
                                'premium' => 'Premium',
                                'single' => 'Salle unique',
                            ])
                            ->required()
                            ->default('basic'),

                        Select::make('country_code')
                            ->label('Pays')
                            ->options(collect(config('billing.countries', []))->mapWithKeys(fn (array $country, string $code): array => [$code => $country['name']])->all())
                            ->live()
                            ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('currency_code', config('billing.countries.'.$state.'.currency', 'XOF')))
                            ->required()
                            ->default('CI'),

                        TextInput::make('currency_code')
                            ->label('Devise de référence')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->default('XOF'),

                        DateTimePicker::make('expires_at')
                            ->label('Fin de validite')
                            ->nullable(),

                        TextInput::make('walk_in_price')
                            ->label('Prix seance unique (FCFA)')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),

                        Toggle::make('is_approved')
                            ->label('Salle validée par le Super Admin')
                            ->default(true),

                        Toggle::make('is_active')
                            ->label('Salle active')
                            ->default(true),
                    ]),
            ]);
    }
}
