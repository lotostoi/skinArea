<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class SteamInventoryPageResult
{
    /**
     * @param  list<SteamInventoryAssetDto>  $items
     */
    public function __construct(
        public array $items,
        public ?int $steamTotalInventoryCount,
        public int $rawAssetCount,
    ) {}
}
