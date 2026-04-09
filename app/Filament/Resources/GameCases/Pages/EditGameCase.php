<?php

namespace App\Filament\Resources\GameCases\Pages;

use App\Filament\Resources\GameCases\GameCaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGameCase extends EditRecord
{
    protected static string $resource = GameCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
