<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WithdrawalRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'admin_comment',
        'processed_at',
        'processed_by',
    ];

    public function casts(): array
    {
        return [
            'status' => WithdrawalRequestStatus::class,
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
