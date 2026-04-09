<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DealStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'market_item_id',
        'price',
        'commission',
        'status',
        'trade_offer_id',
        'cancelled_reason',
        'expires_at',
    ];

    public function casts(): array
    {
        return [
            'status' => DealStatus::class,
            'price' => 'decimal:2',
            'commission' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function marketItem(): BelongsTo
    {
        return $this->belongsTo(MarketItem::class);
    }

    public function scopeForParticipant(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user): void {
            $q->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id);
        });
    }
}
