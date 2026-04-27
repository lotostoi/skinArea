<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CaseOpening;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CaseOpening
 */
class CaseOpeningFeedResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'won_item_price' => $this->won_item_price,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'user' => [
                'id' => $this->user?->id,
                'username' => $this->user?->username,
                'avatar_url' => $this->user?->avatar_url,
            ],
            'case' => [
                'id' => $this->gameCase?->id,
                'name' => $this->gameCase?->name,
            ],
            'item' => [
                'id' => $this->caseItem?->id,
                'name' => $this->caseItem?->name,
                'image_url' => $this->caseItem?->image_url,
                'rarity' => $this->caseItem?->rarity?->value,
                'rarity_color' => $this->caseItem?->rarity?->color(),
            ],
        ];
    }
}
