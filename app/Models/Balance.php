<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BalanceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Balance extends Model
{
    public const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
    ];

    public function casts(): array
    {
        return [
            'type' => BalanceType::class,
            'amount' => 'decimal:2',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
