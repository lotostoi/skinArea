<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CaseLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CaseLevel
 */
class CaseLevelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'name' => $this->name,
            'chance' => $this->chance,
            'items' => CaseItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
