<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CaseOpeningStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseOpening extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'case_id',
        'case_item_id',
        'cost',
        'won_item_price',
        'status',
    ];

    public function casts(): array
    {
        return [
            'status' => CaseOpeningStatus::class,
            'cost' => 'decimal:2',
            'won_item_price' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gameCase(): BelongsTo
    {
        return $this->belongsTo(GameCase::class, 'case_id');
    }

    public function caseItem(): BelongsTo
    {
        return $this->belongsTo(CaseItem::class, 'case_item_id');
    }

    public function upgrades(): HasMany
    {
        return $this->hasMany(Upgrade::class, 'won_case_opening_id');
    }
}
