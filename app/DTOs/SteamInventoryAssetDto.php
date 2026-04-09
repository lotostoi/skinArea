<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ItemCategory;
use App\Enums\ItemRarity;
use App\Enums\ItemWear;

readonly class SteamInventoryAssetDto
{
    public function __construct(
        public string $assetId,
        public string $name,
        public ?string $imageUrl,
        public ItemWear $wear,
        public ?string $floatValue,
        public ItemRarity $rarity,
        public ItemCategory $category,
        public string $classId,
        public string $instanceId,
        public bool $tradable,
    ) {}
}
