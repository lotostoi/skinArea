<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketItems\Pages;

use App\Filament\Resources\MarketItems\MarketItemResource;
use Filament\Resources\Pages\ListRecords;

class ListMarketItems extends ListRecords
{
    protected static string $resource = MarketItemResource::class;
}
