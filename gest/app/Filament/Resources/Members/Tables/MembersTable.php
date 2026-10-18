<?php

namespace App\Filament\Resources\Members\Tables;

use App\Enums\ClientType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchable(['first_name', 'last_name', 'name', 'phone'])
            ->searchPlaceholder('Rechercher par nom, prénom ou téléphone…')
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl('/images/member-avatar.svg'),

                TextColumn::make('last_name')
                    ->label('Nom')
                    ->sortable(),

                TextColumn::make('first_name')
                    ->label('Prénom')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Affichage')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('phone')
                    ->label('Téléphone'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('id_document_number')
                    ->label('N° pièce')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('client_type')
                    ->label('Type')
                    ->formatStateUsing(fn (ClientType $state) => $state->label())
                    ->color(fn (ClientType $state) => match ($state) {
                        ClientType::Subscriber => 'success',
                        ClientType::WalkIn => 'warning',
                    }),

                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('inscription_jour')
                    ->label('Date d’inscription')
                    ->placeholder('Tous les membres')
                    ->options([
                        'today' => 'Inscrits aujourd’hui',
                        'tomorrow' => 'Inscrits demain',
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return;
                        }

                        if ($value === 'today') {
                            $query->whereDate('created_at', now()->toDateString());

                            return;
                        }

                        if ($value === 'tomorrow') {
                            $query->whereDate('created_at', now()->addDay()->toDateString());
                        }
                    }),

                SelectFilter::make('client_type')
                    ->label('Type de client')
                    ->options(collect(ClientType::cases())->mapWithKeys(
                        fn (ClientType $type) => [$type->value => $type->label()]
                    )),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
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
