<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SubscriptionAlerts extends TableWidget
{
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->heading('⚠️  Alertes abonnements')
            ->description('Abonnements expirés ou expirant dans les 7 prochains jours.')
            ->query(fn (): Builder => Subscription::query()
                ->with(['member', 'plan'])
                ->where('status', '!=', SubscriptionStatus::Cancelled->value)
                ->where(function ($query): void {
                    $query
                        // Déjà expirés
                        ->where('status', SubscriptionStatus::Expired->value)
                        // Actifs mais expirant dans 7 jours
                        ->orWhere(function ($q): void {
                            $q->where('status', SubscriptionStatus::Active->value)
                                ->whereDate('end_date', '>=', today()->toDateString())
                                ->whereDate('end_date', '<=', today()->addDays(7)->toDateString());
                        })
                        // Actifs mais déjà passés
                        ->orWhere(function ($q): void {
                            $q->where('status', SubscriptionStatus::Active->value)
                                ->whereDate('end_date', '<', today()->toDateString());
                        });
                })
                ->orderByRaw("CASE WHEN status = 'expired' THEN 1 ELSE 0 END DESC")
                ->orderBy('end_date')
            )
            ->columns([
                TextColumn::make('member.name')
                    ->label('Membre')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('member.phone')
                    ->label('Téléphone')
                    ->placeholder('—'),

                TextColumn::make('plan.name')
                    ->label('Forfait')
                    ->placeholder('—'),

                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Subscription $record): string => Carbon::parse($record->end_date)->lt(today()) ? 'danger' : 'warning'),

                TextColumn::make('days_remaining')
                    ->label('Reste')
                    ->state(function (Subscription $record): string {
                        $days = (int) today()->diffInDays($record->end_date, false);
                        if ($days < 0) {
                            return 'Expiré il y a '.abs($days).'j';
                        }
                        if ($days === 0) {
                            return "Expire aujourd'hui";
                        }

                        return "Dans {$days} j.";
                    })
                    ->badge()
                    ->color(function (Subscription $record): string {
                        $days = (int) today()->diffInDays($record->end_date, false);
                        if ($days < 0) {
                            return 'danger';
                        }
                        if ($days <= 2) {
                            return 'warning';
                        }

                        return 'info';
                    }),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (SubscriptionStatus $state): string => $state->label())
                    ->color(fn (SubscriptionStatus $state): string => match ($state) {
                        SubscriptionStatus::Pending => 'warning',
                        SubscriptionStatus::Active => 'warning',
                        SubscriptionStatus::Expired => 'danger',
                        SubscriptionStatus::Cancelled => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Fiche')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Subscription $record): string => SubscriptionResource::getUrl('edit', ['record' => $record->member_id])),
            ])
            ->defaultPaginationPageOption(5)
            ->poll('60s');
    }
}
