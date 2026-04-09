<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameCase extends Model
{
    protected $table = 'cases';

    protected $fillable = [
        'name',
        'image_url',
        'price',
        'category_id',
        'sort_order',
        'is_active',
    ];

    public function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'category_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(CaseLevel::class, 'case_id');
    }

    public function openings(): HasMany
    {
        return $this->hasMany(CaseOpening::class, 'case_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
