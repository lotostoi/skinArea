<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Плейсхолдер под выдачу целей апгрейда; позже можно заменить моделью/DTO.
 *
 * @mixin object
 */
class UpgradeItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'] ?? null,
            'image_url' => $this->resource['image_url'] ?? null,
            'price' => $this->resource['price'] ?? null,
        ];
    }
}
