<?php

namespace App\Filament\Resources\CaseItems\Pages;

use App\Filament\Resources\CaseItems\CaseItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCaseItems extends ListRecords
{
    protected static string $resource = CaseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
