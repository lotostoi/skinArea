<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Balance
 */
class BalanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type->value,
            'amount' => $this->amount,
            'updated_at' => $this->updated_at,
        ];
    }
}
