<?php

namespace App\Filament\Resources\SupportMessages\Pages;

use App\Filament\Resources\SupportMessages\SupportMessageResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSupportMessages extends ManageRecords
{
    protected static string $resource = SupportMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
