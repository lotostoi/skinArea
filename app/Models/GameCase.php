<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\CaseEconomyValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameCase extends Model
{
    protected $table = 'cases';

    protected $fillable = [
        'name',
        'description',
        'shadow_color',
        'image_url',
        'price',
        'category_id',
        'sort_order',
        'is_active',
        'is_featured_on_home',
        'is_manual_admin_case',
    ];

    public function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured_on_home' => 'boolean',
            'is_manual_admin_case' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $case): void {
            if (! array_key_exists('is_manual_admin_case', $case->getAttributes())) {
                $case->is_manual_admin_case = true;
            }
        });

        static::saving(function (self $case): void {
            /** @var CaseEconomyValidator $validator */
            $validator = app(CaseEconomyValidator::class);
            $validator->validate($case, $case->levels()->get());
        });
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

    public function fundAdjustments(): HasMany
    {
        return $this->hasMany(CaseFundAdjustment::class, 'case_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeaturedOnHome(Builder $query): Builder
    {
        return $query->where('is_featured_on_home', true);
    }
}
