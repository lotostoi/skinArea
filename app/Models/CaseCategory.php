<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseCategory extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
        'is_visible',
    ];

    public function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function gameCases(): HasMany
    {
        return $this->hasMany(GameCase::class, 'category_id');
    }
}
