<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CaseItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CaseItem
 */
class CaseItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image_url' => $this->image_url,
            'price' => $this->price,
            'wear' => $this->wear->value,
            'rarity' => $this->rarity->value,
        ];
    }
}
