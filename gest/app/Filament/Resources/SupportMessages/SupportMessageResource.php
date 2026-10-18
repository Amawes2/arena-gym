<?php

namespace App\Filament\Resources\SupportMessages;

use App\Enums\UserRole;
use App\Filament\Resources\SupportMessages\Pages\ManageSupportMessages;
use App\Models\Gym;
use App\Models\SupportMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SupportMessageResource extends Resource
{
    protected static ?string $model = SupportMessage::class;

    protected static ?string $recordTitleAttribute = 'gym.name';

    protected static ?string $navigationLabel = 'Messages des salles';

    protected static ?string $modelLabel = 'message';

    protected static ?string $pluralModelLabel = 'messages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function canAccess(): bool
    {
        return auth()->user()?->roleEnum() === UserRole::SuperAdmin;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('gym_id')
                    ->label('Salle')
                    ->options(fn (): array => Gym::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required(),
                Textarea::make('body')
                    ->label('Message')
                    ->required()
                    ->maxLength(4000)
                    ->rows(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('gym.name')
                    ->label('Salle')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sender.name')
                    ->label('Expéditeur')
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Sujet')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'renewal' ? 'Renouvellement' : 'Message'),
                TextColumn::make('body')
                    ->label('Contenu')
                    ->limit(100)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Reçu le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('gym_id')
                    ->label('Salle')
                    ->options(fn (): array => Gym::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('category')
                    ->label('Type')
                    ->options(['message' => 'Message', 'renewal' => 'Renouvellement']),
            ])
            ->recordActions([
                Action::make('reply')
                    ->label('Répondre')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->schema([
                        Textarea::make('body')
                            ->label('Votre réponse')
                            ->required()
                            ->minLength(2)
                            ->maxLength(4000)
                            ->rows(5),
                    ])
                    ->action(function (array $data, SupportMessage $record): void {
                        SupportMessage::query()->create([
                            'gym_id' => $record->gym_id,
                            'user_id' => auth()->id(),
                            'sender_role' => UserRole::SuperAdmin->value,
                            'category' => 'message',
                            'body' => trim($data['body']),
                        ]);

                        $record->forceFill(['read_at' => now()])->save();
                    }),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSupportMessages::route('/'),
        ];
    }
}
