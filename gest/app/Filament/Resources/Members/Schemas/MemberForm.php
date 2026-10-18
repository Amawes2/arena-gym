<?php

namespace App\Filament\Resources\Members\Schemas;

use App\Enums\ClientType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('last_name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(120),

                        TextInput::make('first_name')
                            ->label('Prénom')
                            ->required()
                            ->maxLength(120),

                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),

                        TextInput::make('id_document_number')
                            ->label('N° de pièce d’identité')
                            ->maxLength(80)
                            ->nullable(),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('client_type')
                            ->label('Type de client')
                            ->options(collect(ClientType::cases())->mapWithKeys(
                                fn (ClientType $type) => [$type->value => $type->label()]
                            ))
                            ->required()
                            ->default(ClientType::Subscriber->value),
                    ]),

                FileUpload::make('photo_path')
                    ->label('Photo')
                    ->image()
                    ->directory('members/photos')
                    ->visibility('public')
                    ->nullable()
                    ->columnSpanFull(),

                Textarea::make('bio')
                    ->label('Biographie / Notes')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
