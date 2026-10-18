<?php

namespace App\Filament\Platform\Resources\Gyms\Tables;

use App\Models\Gym;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GymsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Salle')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('Responsable')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('plan_saas')
                    ->label('Formule')
                    ->badge(),

                TextColumn::make('country_code')
                    ->label('Pays')
                    ->formatStateUsing(fn (?string $state): string => config('billing.countries.'.strtoupper((string) $state).'.name', (string) $state)),

                TextColumn::make('currency_code')
                    ->label('Devise')
                    ->badge(),

                TextColumn::make('saas_status')
                    ->label('Etat')
                    ->state(fn (Gym $record): string => $record->getSaasStatusLabel())
                    ->badge()
                    ->color(fn (Gym $record): string => $record->getSaasStatusColor()),

                IconColumn::make('is_approved')
                    ->label('Validée')
                    ->boolean(),

                TextColumn::make('provisional_subscriptions_created')
                    ->label('Abonnements provisoires')
                    ->numeric(),

                TextColumn::make('expires_at')
                    ->label('Expiration')
                    ->dateTime('d/m/Y')
                    ->placeholder('Sans date')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->stackedOnMobile()
            ->filters([
                TernaryFilter::make('is_active')->label('Etat actif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }
}
