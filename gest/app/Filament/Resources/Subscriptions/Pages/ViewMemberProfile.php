<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Enums\CheckInType;
use App\Enums\ClientType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Member;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewMemberProfile extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderHeading(): string
    {
        return "Profil de {$this->getRecord()->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Modifier la fiche'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        /** @var Member $member */
        $member = $this->getRecord();
        $sub = $member->activeSubscription;

        return $schema->components([
            // ── Identité ──────────────────────────────────────────────────
            Section::make('Identité')
                ->icon('heroicon-o-user')
                ->columns(3)
                ->schema([
                    ImageEntry::make('photo_path')
                        ->label('Photo')
                        ->circular()
                        ->defaultImageUrl('/images/member-avatar.svg'),

                    Grid::make(2)
                        ->columnSpan(2)
                        ->schema([
                            TextEntry::make('last_name')->label('Nom'),
                            TextEntry::make('first_name')->label('Prénom'),
                            TextEntry::make('phone')->label('Téléphone'),
                            TextEntry::make('email')->label('Email')->placeholder('—'),
                            TextEntry::make('id_document_number')->label('N° pièce')->placeholder('—'),
                            TextEntry::make('client_type')
                                ->label('Type')
                                ->badge()
                                ->formatStateUsing(fn (ClientType $state): string => $state->label())
                                ->color(fn (ClientType $state): string => match ($state) {
                                    ClientType::Subscriber => 'success',
                                    ClientType::WalkIn => 'warning',
                                }),
                        ]),
                ]),

            // ── Abonnement actif ──────────────────────────────────────────
            Section::make('Abonnement actif')
                ->icon('heroicon-o-calendar-days')
                ->columns(4)
                ->schema($sub ? [
                    TextEntry::make('plan')
                        ->label('Forfait')
                        ->state($sub->plan?->name ?? '—')
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('sub_status')
                        ->label('Statut')
                        ->state($sub->status->label())
                        ->badge()
                        ->color(match ($sub->status) {
                            SubscriptionStatus::Pending => 'warning',
                            SubscriptionStatus::Active => 'success',
                            SubscriptionStatus::Expired => 'danger',
                            SubscriptionStatus::Cancelled => 'gray',
                        }),

                    TextEntry::make('sub_start')
                        ->label('Début')
                        ->state($sub->start_date->format('d/m/Y')),

                    TextEntry::make('sub_end')
                        ->label('Expire le')
                        ->state($sub->end_date->format('d/m/Y'))
                        ->color($sub->end_date->lt(today()) ? 'danger' : 'success'),

                    TextEntry::make('sessions_left')
                        ->label('Séances restantes')
                        ->state($sub->sessions_remaining === null ? 'Illimité ∞' : (string) $sub->sessions_remaining)
                        ->badge()
                        ->color(function () use ($sub): string {
                            if ($sub->sessions_remaining === null) {
                                return 'success';
                            }
                            if ($sub->sessions_remaining <= 2) {
                                return 'danger';
                            }
                            if ($sub->sessions_remaining <= 5) {
                                return 'warning';
                            }

                            return 'info';
                        }),
                ] : [
                    TextEntry::make('no_sub')
                        ->label('')
                        ->state('Aucun abonnement actif')
                        ->columnSpanFull()
                        ->color('danger'),
                ]),

            // ── Historique abonnements ────────────────────────────────────
            Section::make('Historique abonnements')
                ->icon('heroicon-o-clock')
                ->collapsed()
                ->schema([
                    RepeatableEntry::make('subscriptions')
                        ->label('')
                        ->schema([
                            TextEntry::make('plan.name')->label('Forfait')->badge()->color('primary'),
                            TextEntry::make('start_date')->label('Début')->date('d/m/Y'),
                            TextEntry::make('end_date')->label('Fin')->date('d/m/Y'),
                            TextEntry::make('status')
                                ->label('Statut')
                                ->badge()
                                ->formatStateUsing(fn (SubscriptionStatus $state): string => $state->label())
                                ->color(fn (SubscriptionStatus $state): string => match ($state) {
                                    SubscriptionStatus::Pending => 'warning',
                                    SubscriptionStatus::Active => 'success',
                                    SubscriptionStatus::Expired => 'danger',
                                    SubscriptionStatus::Cancelled => 'gray',
                                }),
                        ])
                        ->columns(4),
                ]),

            // ── Historique paiements ──────────────────────────────────────
            Section::make('Historique paiements')
                ->icon('heroicon-o-banknotes')
                ->collapsed()
                ->schema([
                    RepeatableEntry::make('payments')
                        ->label('')
                        ->schema([
                            TextEntry::make('amount')
                                ->label('Montant')
                                ->money('XOF')
                                ->badge()
                                ->color('success'),
                            TextEntry::make('payment_method')
                                ->label('Mode')
                                ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                            TextEntry::make('status')
                                ->label('Statut')
                                ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                                ->badge()
                                ->color(fn (PaymentStatus $state): string => match ($state) {
                                    PaymentStatus::Completed => 'success',
                                    PaymentStatus::Pending => 'warning',
                                    PaymentStatus::Failed => 'danger',
                                }),
                            TextEntry::make('paid_at')->label('Date')->date('d/m/Y H:i'),
                        ])
                        ->columns(4),
                ]),

            // ── Dernières entrées ─────────────────────────────────────────
            Section::make('Dernières entrées (check-ins)')
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->collapsed()
                ->schema([
                    RepeatableEntry::make('checkIns')
                        ->label('')
                        ->schema([
                            TextEntry::make('type')
                                ->label('Type')
                                ->formatStateUsing(fn (CheckInType $state): string => $state->label())
                                ->badge()
                                ->color(fn (CheckInType $state): string => match ($state) {
                                    CheckInType::Subscription => 'success',
                                    CheckInType::WalkIn => 'warning',
                                }),
                            TextEntry::make('checked_in_at')->label('Date')->dateTime('d/m/Y H:i'),
                            TextEntry::make('subscription.plan.name')->label('Forfait')->placeholder('—'),
                            TextEntry::make('notes')->label('Notes')->placeholder('—'),
                        ])
                        ->columns(4),
                ]),
        ]);
    }
}
