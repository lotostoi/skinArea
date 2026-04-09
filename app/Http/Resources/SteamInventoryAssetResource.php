<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\SteamInventoryAssetDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SteamInventoryAssetDto $resource
 */
class SteamInventoryAssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $d = $this->resource;

        return [
            'asset_id' => $d->assetId,
            'name' => $d->name,
            'image_url' => $d->imageUrl,
            'wear' => $d->wear->value,
            'float_value' => $d->floatValue,
            'rarity' => $d->rarity->value,
            'category' => $d->category->value,
            'tradable' => $d->tradable,
        ];
    }
}
