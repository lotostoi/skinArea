<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ItemCategory;
use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Enums\MarketItemStatus;
use Database\Factories\MarketItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketItem extends Model
{
    /** @use HasFactory<MarketItemFactory> */
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'asset_id',
        'name',
        'image_url',
        'wear',
        'float_value',
        'rarity',
        'category',
        'price',
        'status',
        'skin_catalog_external_id',
    ];

    public function casts(): array
    {
        return [
            'wear' => ItemWear::class,
            'rarity' => ItemRarity::class,
            'category' => ItemCategory::class,
            'status' => MarketItemStatus::class,
            'float_value' => 'decimal:18',
            'price' => 'decimal:2',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function catalogSkin(): BelongsTo
    {
        return $this->belongsTo(SkinCatalogItem::class, 'skin_catalog_external_id', 'external_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', MarketItemStatus::Active);
    }
}
