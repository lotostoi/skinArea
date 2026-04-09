<?php

namespace App\Filament\Resources\GameCases\Pages;

use App\Filament\Resources\GameCases\GameCaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGameCases extends ListRecords
{
    protected static string $resource = GameCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
