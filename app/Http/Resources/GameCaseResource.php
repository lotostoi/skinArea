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
            'is_active' => $this->is_active,
            'is_featured_on_home' => $this->is_featured_on_home,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn (): ?array => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'sort_order' => $this->category->sort_order,
            ] : null),
            'levels' => CaseLevelResource::collection($this->whenLoaded('levels')),
        ];
    }
}
