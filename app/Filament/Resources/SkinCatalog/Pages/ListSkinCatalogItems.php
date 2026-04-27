<?php

declare(strict_types=1);

namespace App\Filament\Resources\SkinCatalog\Pages;

use App\Filament\Resources\SkinCatalog\SkinCatalogItemResource;
use Filament\Resources\Pages\ListRecords;

class ListSkinCatalogItems extends ListRecords
{
    protected static string $resource = SkinCatalogItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
