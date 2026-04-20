<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BalanceType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'balance_type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'metadata',
        'posted_at',
        'reversed_at',
        'reverses_transaction_id',
        'idempotency_key',
    ];

    public function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'balance_type' => BalanceType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_transaction_id');
    }

    public function reversedBy(): HasMany
    {
        return $this->hasMany(self::class, 'reverses_transaction_id');
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::Posted);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::Pending);
    }

    public function scopeOfBalance(Builder $query, BalanceType $type): Builder
    {
        return $query->where('balance_type', $type);
    }

    public function scopeMain(Builder $query): Builder
    {
        return $query->where('balance_type', BalanceType::Main);
    }

    public function scopeHold(Builder $query): Builder
    {
        return $query->where('balance_type', BalanceType::Hold);
    }
}
