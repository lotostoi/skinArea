<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseLevel extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'case_id',
        'level',
        'name',
        'chance',
    ];

    public function casts(): array
    {
        return [
            'chance' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function gameCase(): BelongsTo
    {
        return $this->belongsTo(GameCase::class, 'case_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CaseItem::class, 'case_level_id');
    }
}
