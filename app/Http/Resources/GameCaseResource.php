<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GameCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GameCase
 */
class GameCaseResource extends JsonResource
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
            'sort_order' => $this->sort_order,
            'levels' => CaseLevelResource::collection($this->whenLoaded('levels')),
        ];
    }
}
