<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkinCatalogItem extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'image_url',
        'rarity',
        'category',
        'weapon_name',
        'last_synced_at',
    ];

    public function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public function marketItems(): HasMany
    {
        return $this->hasMany(MarketItem::class, 'skin_catalog_external_id', 'external_id');
    }

    public function caseItems(): HasMany
    {
        return $this->hasMany(CaseItem::class, 'skin_catalog_external_id', 'external_id');
    }
}
