<?php

namespace App\Filament\Resources\Plans\Tables;

use App\Enums\PlanBillingPeriod;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Prix')
                    ->money('XOF')
                    ->sortable(),

                TextColumn::make('billing_period')
                    ->label('Facturation')
                    ->formatStateUsing(function (mixed $state): string {
                        if ($state instanceof PlanBillingPeriod) {
                            return $state->label();
                        }

                        if (is_string($state) && $state !== '') {
                            return PlanBillingPeriod::from($state)->label();
                        }

                        return '—';
                    })
                    ->badge()
                    ->sortable(),

                TextColumn::make('duration_days')
                    ->label('Durée (j)')
                    ->formatStateUsing(fn (?int $state) => $state === null ? '—' : "{$state} j")
                    ->sortable(),

                TextColumn::make('sessions_limit')
                    ->label('Séances')
                    ->formatStateUsing(fn (?int $state) => $state === null ? 'Illimité' : $state)
                    ->badge()
                    ->color(fn (?int $state) => $state === null ? 'success' : 'info'),

                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->stackedOnMobile()
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Plans actifs'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->fetchSelectedRecords(),
                ]),
            ]);
    }
}
