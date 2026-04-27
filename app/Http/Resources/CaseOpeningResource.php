<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CaseOpening;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CaseOpening
 */
class CaseOpeningResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cost' => $this->cost,
            'won_item_price' => $this->won_item_price,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'source' => 'case_open',
            'case' => new GameCaseResource($this->whenLoaded('gameCase')),
            'won_item' => new CaseItemResource($this->whenLoaded('caseItem')),
        ];
    }
}
