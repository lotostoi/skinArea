<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ItemCategory;
use App\Enums\ItemRarity;
use App\Models\SkinCatalogItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SkinCatalogSyncService
{
    /**
     * @return array{synced: int, skipped: int}
     */
    public function syncFromUrl(string $url, ?int $limit = null, bool $dryRun = false): array
    {
        $response = Http::timeout((int) config('skinsarena.skin_catalog.http_timeout_seconds', 120))
            ->withHeaders([
                'User-Agent' => (string) config('skinsarena.skin_catalog.user_agent', 'SkinsArena/1.0'),
                'Accept' => 'application/json',
            ])
            ->get($url);

        $response->throw();

        /** @var mixed $decoded */
        $decoded = $response->json();

        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('Ответ источника не является JSON-массивом.');
        }

        $now = Carbon::now();
        $synced = 0;
        $skipped = 0;
        $batch = [];

        foreach ($decoded as $row) {
            if ($limit !== null && $synced >= $limit) {
                break;
            }

            if (! is_array($row)) {
                $skipped++;

                continue;
            }

            $mapped = $this->mapRow($row);
            if ($mapped === null) {
                $skipped++;

                continue;
            }

            $mapped['last_synced_at'] = $now;
            $mapped['created_at'] = $now;
            $mapped['updated_at'] = $now;
            $batch[] = $mapped;
            $synced++;

            if (count($batch) >= 100) {
                if (! $dryRun) {
                    $this->upsertBatch($batch);
                }
                $batch = [];
            }
        }

        if ($batch !== [] && ! $dryRun) {
            $this->upsertBatch($batch);
        }

        return ['synced' => $synced, 'skipped' => $skipped];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function mapRow(array $row): ?array
    {
        $externalId = $row['id'] ?? null;
        $name = $row['name'] ?? null;
        if (! is_string($externalId) || $externalId === '' || ! is_string($name) || $name === '') {
            return null;
        }

        $image = $row['image'] ?? null;
        $imageUrl = is_string($image) ? $image : null;

        $rarityId = Arr::get($row, 'rarity.id');
        $categoryId = Arr::get($row, 'category.id');
        $weaponName = Arr::get($row, 'weapon.name');

        return [
            'external_id' => $externalId,
            'name' => $name,
            'image_url' => $imageUrl,
            'rarity' => is_string($rarityId) ? $this->mapRarity($rarityId) : null,
            'category' => is_string($categoryId) ? $this->mapCategory($categoryId) : null,
            'weapon_name' => is_string($weaponName) ? $weaponName : null,
        ];
    }

    private function mapRarity(string $rarityId): ?string
    {
        $map = [
            'rarity_default' => ItemRarity::ConsumerGrade->value,
            'rarity_common_weapon' => ItemRarity::ConsumerGrade->value,
            'rarity_uncommon_weapon' => ItemRarity::IndustrialGrade->value,
            'rarity_rare_weapon' => ItemRarity::MilSpec->value,
            'rarity_mythical_weapon' => ItemRarity::Restricted->value,
            'rarity_legendary_weapon' => ItemRarity::Classified->value,
            'rarity_ancient' => ItemRarity::Covert->value,
            'rarity_ancient_weapon' => ItemRarity::Covert->value,
            'rarity_contraband' => ItemRarity::Contraband->value,
        ];

        return $map[$rarityId] ?? null;
    }

    private function mapCategory(string $categoryId): ?string
    {
        $map = [
            'sfui_invpanel_filter_gloves' => ItemCategory::Gloves->value,
            'sfui_invpanel_filter_melee' => ItemCategory::Knives->value,
            'sfui_invpanel_filter_pistol' => ItemCategory::Pistols->value,
            'sfui_invpanel_filter_rifle' => ItemCategory::Rifles->value,
            'sfui_invpanel_filter_smg' => ItemCategory::SMGs->value,
            'sfui_invpanel_filter_heavy' => ItemCategory::Heavy->value,
        ];

        return $map[$categoryId] ?? ItemCategory::Other->value;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function upsertBatch(array $rows): void
    {
        SkinCatalogItem::query()->upsert(
            $rows,
            ['external_id'],
            ['name', 'image_url', 'rarity', 'category', 'weapon_name', 'last_synced_at', 'updated_at'],
        );
    }

    /**
     * @return array{synced: int, skipped: int}
     */
    public function syncFromConfiguredSource(?int $limit = null, bool $dryRun = false): array
    {
        $url = (string) config('skinsarena.skin_catalog.source_url');

        if ($url === '') {
            throw new \RuntimeException('Не задан skinsarena.skin_catalog.source_url (.env SKIN_CATALOG_SOURCE_URL).');
        }

        try {
            return $this->syncFromUrl($url, $limit, $dryRun);
        } catch (Throwable $e) {
            Log::error('skin_catalog.sync_failed', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
