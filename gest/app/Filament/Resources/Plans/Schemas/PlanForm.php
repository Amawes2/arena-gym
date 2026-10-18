<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Enums\PlanBillingPeriod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom du plan')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('price')
                            ->label('Prix (FCFA)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('FCFA'),

                        Select::make('billing_period')
                            ->label('Période de facturation')
                            ->options(collect([
                                PlanBillingPeriod::Monthly,
                                PlanBillingPeriod::Quarterly,
                                PlanBillingPeriod::Annual,
                                PlanBillingPeriod::Custom,
                            ])->mapWithKeys(
                                fn (PlanBillingPeriod $p) => [$p->value => $p->label()]
                            ))
                            ->required()
                            ->default(PlanBillingPeriod::Monthly->value)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                if ($state === PlanBillingPeriod::Custom->value) {
                                    $set('duration_days', null);

                                    return;
                                }

                                $days = match ($state) {
                                    PlanBillingPeriod::Monthly->value => 30,
                                    PlanBillingPeriod::Quarterly->value => 90,
                                    PlanBillingPeriod::Annual->value => 365,
                                    default => max(1, (int) $get('duration_days')),
                                };

                                $set('duration_days', $days);
                            }),

                        TextInput::make('duration_days')
                            ->label('Durée (jours)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(30)
                            ->disabled(fn (Get $get): bool => $get('billing_period') !== PlanBillingPeriod::Custom->value)
                            ->dehydrated()
                            ->helperText(fn (Get $get): string => $get('billing_period') === PlanBillingPeriod::Custom->value
                                ? 'Entrez la durée personnalisée.'
                                : 'Calculée automatiquement selon la période choisie.'),

                        TextInput::make('sessions_limit')
                            ->label('Nb séances (vide = illimité)')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('Laisser vide pour un accès illimité.'),

                        Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->inline(false),
                    ]),

                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
