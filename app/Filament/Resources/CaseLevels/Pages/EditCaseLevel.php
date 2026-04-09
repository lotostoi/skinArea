<?php

namespace App\Filament\Resources\CaseLevels\Pages;

use App\Filament\Resources\CaseLevels\CaseLevelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCaseLevel extends EditRecord
{
    protected static string $resource = CaseLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
