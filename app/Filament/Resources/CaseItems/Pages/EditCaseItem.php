<?php

namespace App\Filament\Resources\CaseItems\Pages;

use App\Filament\Resources\CaseItems\CaseItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCaseItem extends EditRecord
{
    protected static string $resource = CaseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
