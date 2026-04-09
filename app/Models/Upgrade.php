<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Upgrade extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'bet_amount',
        'target_item_name',
        'target_item_price',
        'chance',
        'is_won',
        'won_case_opening_id',
    ];

    public function casts(): array
    {
        return [
            'bet_amount' => 'decimal:2',
            'target_item_price' => 'decimal:2',
            'chance' => 'decimal:2',
            'is_won' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wonCaseOpening(): BelongsTo
    {
        return $this->belongsTo(CaseOpening::class, 'won_case_opening_id');
    }
}
