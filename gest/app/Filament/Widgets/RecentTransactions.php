<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\DashboardPeriod;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTransactions extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Transactions recentes')
            ->description('Derniers paiements enregistres pour votre salle.')
            ->query(function (): Builder {
                $period = DashboardPeriod::fromFilter($this->filters['period'] ?? null);
                [$start, $end] = $period->bounds();

                return Payment::query()
                    ->with(['member', 'subscription'])
                    ->where(function (Builder $query) use ($start, $end): void {
                        $query
                            ->whereBetween('paid_at', [$start, $end])
                            ->orWhere(function (Builder $pendingQuery) use ($start, $end): void {
                                $pendingQuery
                                    ->whereNull('paid_at')
                                    ->whereBetween('created_at', [$start, $end]);
                            });
                    })
                    ->latest('created_at');
            })
            ->columns([
                Tables\Columns\TextColumn::make('member.name')
                    ->label('Client')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF', locale: 'fr')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Mode')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => method_exists($state, 'label') ? $state->label() : ucfirst((string) $state)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => method_exists($state, 'label') ? $state->label() : ucfirst((string) $state))
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Completed => 'success',
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Failed => 'danger',
                    }),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Date')
                    ->state(fn (Payment $record) => $record->paid_at ?? $record->created_at)
                    ->since()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('created_at', $direction)),
            ])
            ->defaultPaginationPageOption(5)
            ->poll('60s');
    }
}
