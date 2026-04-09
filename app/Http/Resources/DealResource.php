<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Deal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Deal
 */
class DealResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price' => $this->price,
            'commission' => $this->commission,
            'status' => $this->status->value,
            'trade_offer_id' => $this->trade_offer_id,
            'cancelled_reason' => $this->cancelled_reason,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'market_item' => new MarketItemResource($this->whenLoaded('marketItem')),
        ];
    }
}
