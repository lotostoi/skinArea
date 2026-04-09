<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MarketItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MarketItem
 */
class MarketItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'name' => $this->name,
            'image_url' => $this->image_url,
            'wear' => $this->wear->value,
            'float_value' => $this->float_value,
            'rarity' => $this->rarity->value,
            'category' => $this->category->value,
            'price' => $this->price,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'seller' => new UserResource($this->whenLoaded('seller')),
        ];
    }
}
