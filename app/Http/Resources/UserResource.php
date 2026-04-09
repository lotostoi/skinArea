<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'steam_id' => $this->steam_id,
            'username' => $this->username,
            'avatar_url' => $this->avatar_url,
            'trade_url' => $this->trade_url,
            'role' => $this->role->value,
            'balances' => $this->when(
                $this->relationLoaded('balances'),
                fn (): array => BalanceResource::collection($this->balances)->resolve($request),
            ),
        ];
    }
}
