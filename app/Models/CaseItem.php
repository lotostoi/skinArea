<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseItem extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'case_level_id',
        'name',
        'image_url',
        'price',
        'wear',
        'rarity',
        'skin_catalog_external_id',
    ];

    public function casts(): array
    {
        return [
            'wear' => ItemWear::class,
            'rarity' => ItemRarity::class,
            'price' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(CaseLevel::class, 'case_level_id');
    }

    public function catalogSkin(): BelongsTo
    {
        return $this->belongsTo(SkinCatalogItem::class, 'skin_catalog_external_id', 'external_id');
    }

    public function openings(): HasMany
    {
        return $this->hasMany(CaseOpening::class, 'case_item_id');
    }
}
