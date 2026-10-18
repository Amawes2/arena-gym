<?php

namespace App\Filament\Resources\CheckIns\Tables;

use App\Enums\CheckInType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CheckInsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.name')
                    ->label('Membre')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (CheckInType $state) => $state->label())
                    ->color(fn (CheckInType $state) => match ($state) {
                        CheckInType::Subscription => 'success',
                        CheckInType::WalkIn => 'warning',
                    }),

                TextColumn::make('subscription.plan.name')
                    ->label('Plan')
                    ->placeholder('—')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('checked_in_at')
                    ->label('Heure d\'entrée')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('type')
                    ->label('Type d\'entrée')
                    ->options(collect(CheckInType::cases())->mapWithKeys(
                        fn (CheckInType $t) => [$t->value => $t->label()]
                    )),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->fetchSelectedRecords(),
                ]),
            ])
            ->defaultSort('checked_in_at', 'desc');
    }
}
