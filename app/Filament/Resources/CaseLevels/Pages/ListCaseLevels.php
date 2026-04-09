<?php

namespace App\Filament\Resources\CaseLevels\Pages;

use App\Filament\Resources\CaseLevels\CaseLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCaseLevels extends ListRecords
{
    protected static string $resource = CaseLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
