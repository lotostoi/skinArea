<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'balance_type' => $this->balance_type->value,
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reverses_transaction_id' => $this->reverses_transaction_id,
            'metadata' => $this->metadata,
            'posted_at' => $this->posted_at,
            'reversed_at' => $this->reversed_at,
            'created_at' => $this->created_at,
        ];
    }
}
