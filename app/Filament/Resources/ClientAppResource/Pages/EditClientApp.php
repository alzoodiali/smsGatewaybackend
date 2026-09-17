<?php

namespace App\Filament\Resources\ClientAppResource\Pages;

use App\Filament\Resources\ClientAppResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientApp extends EditRecord
{
    protected static string $resource = ClientAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
