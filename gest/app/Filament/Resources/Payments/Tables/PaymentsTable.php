<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.name')
                    ->label('Membre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),

                BadgeColumn::make('payment_method')
                    ->label('Mode')
                    ->formatStateUsing(fn (PaymentMethod $state) => $state->label())
                    ->color(fn (PaymentMethod $state) => match ($state) {
                        PaymentMethod::Cash => 'gray',
                        PaymentMethod::MobileMoney => 'warning',
                        PaymentMethod::Wave => 'info',
                        PaymentMethod::OrangeMoney => 'warning',
                        PaymentMethod::MtnMoney => 'warning',
                        PaymentMethod::Card => 'info',
                        PaymentMethod::BankTransfer => 'primary',
                    }),

                TextColumn::make('reference')
                    ->label('Référence')
                    ->placeholder('—')
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (PaymentStatus $state) => $state->label())
                    ->color(fn (PaymentStatus $state) => match ($state) {
                        PaymentStatus::Completed => 'success',
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Failed => 'danger',
                    }),

                TextColumn::make('paid_at')
                    ->label('Date paiement')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('payment_method')
                    ->label('Mode de paiement')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(
                        fn (PaymentMethod $m) => [$m->value => $m->label()]
                    )),

                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(
                        fn (PaymentStatus $s) => [$s->value => $s->label()]
                    )),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->fetchSelectedRecords(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
