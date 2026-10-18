<?php

namespace App\Filament\Platform\Resources\Gyms\Pages;

use App\Filament\Platform\Resources\Gyms\GymResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGym extends EditRecord
{
    protected static string $resource = GymResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
