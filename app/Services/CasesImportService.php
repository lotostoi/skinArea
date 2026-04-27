<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Models\CaseCategory;
use App\Models\CaseItem;
use App\Models\CaseLevel;
use App\Models\GameCase;
use App\Support\DemoDataMarkers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CasesImportService
{
    /**
     * Стандартные шансы выпадения CS2 по редкости предмета.
     * level => [name, chance, rarity_value]
     *
     * @var array<string, array{level: int, name: string, chance: float, rarity: string}>
     */
    private const RARITY_LEVEL_MAP = [
        'rarity_rare_weapon' => ['level' => 1, 'name' => 'Армейское',      'chance' => 79.92, 'rarity' => 'mil_spec'],
        'rarity_mythical_weapon' => ['level' => 2, 'name' => 'Запрещённое',    'chance' => 15.98, 'rarity' => 'restricted'],
        'rarity_legendary_weapon' => ['level' => 3, 'name' => 'Засекреченное',  'chance' => 3.20,  'rarity' => 'classified'],
        'rarity_ancient_weapon' => ['level' => 4, 'name' => 'Тайное',         'chance' => 0.64,  'rarity' => 'covert'],
        'rarity_ancient' => ['level' => 4, 'name' => 'Тайное',         'chance' => 0.64,  'rarity' => 'covert'],
        'rarity_contraband' => ['level' => 4, 'name' => 'Контрабанда',    'chance' => 0.64,  'rarity' => 'contraband'],
    ];

    private const RARE_SPECIAL_LEVEL = 5;

    private const RARE_SPECIAL_NAME = 'Rare Special Item';

    private const RARE_SPECIAL_CHANCE = 0.26;

    /**
     * @return array{cases: int, levels: int, items: int, skipped: int}
     */
    public function importFromConfiguredSource(
        ?int $limit = null,
        ?string $filterName = null,
        bool $dryRun = false,
        bool $force = false,
    ): array {
        $url = (string) config('skinsarena.skin_catalog.crates_source_url');

        if ($url === '') {
            throw new \RuntimeException('Не задан skinsarena.skin_catalog.crates_source_url (.env SKIN_CRATES_SOURCE_URL).');
        }

        try {
            return $this->importFromUrl($url, $limit, $filterName, $dryRun, $force);
        } catch (Throwable $e) {
            Log::error('cases_import.failed', ['url' => $url, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * @return array{cases: int, levels: int, items: int, skipped: int}
     */
    public function importFromUrl(
        string $url,
        ?int $limit = null,
        ?string $filterName = null,
        bool $dryRun = false,
        bool $force = false,
    ): array {
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

        $cases = 0;
        $levels = 0;
        $items = 0;
        $skipped = 0;

        $categoryId = $dryRun ? 0 : $this->findOrCreateCategory();

        foreach ($decoded as $crate) {
            if ($limit !== null && $cases >= $limit) {
                break;
            }

            if (! is_array($crate)) {
                $skipped++;

                continue;
            }

            if (($crate['type'] ?? null) !== 'Case') {
                continue;
            }

            $name = $crate['name'] ?? null;
            $contains = $crate['contains'] ?? [];
            $rare = $crate['contains_rare'] ?? [];

            if (! is_string($name) || $name === '') {
                $skipped++;

                continue;
            }

            if (! is_array($contains) || count($contains) === 0) {
                $skipped++;

                continue;
            }

            if ($filterName !== null && ! str_contains(mb_strtolower($name), mb_strtolower($filterName))) {
                continue;
            }

            $result = $this->processCase($crate, $categoryId, $dryRun, $force);
            $cases++;
            $levels += $result['levels'];
            $items += $result['items'];
        }

        return compact('cases', 'levels', 'items', 'skipped');
    }

    /**
     * @param  array<string, mixed>  $crate
     * @return array{levels: int, items: int}
     */
    private function processCase(array $crate, int $categoryId, bool $dryRun, bool $force): array
    {
        $name = (string) ($crate['name'] ?? '');
        $imageUrl = is_string($crate['image'] ?? null) ? $crate['image'] : null;
        $contains = is_array($crate['contains'] ?? null) ? $crate['contains'] : [];
        $rare = is_array($crate['contains_rare'] ?? null) ? $crate['contains_rare'] : [];

        // Сгруппируем contains по уровню редкости
        $grouped = $this->groupByLevel($contains);

        if (count($grouped) === 0 && count($rare) === 0) {
            return ['levels' => 0, 'items' => 0];
        }

        if ($dryRun) {
            $levelCount = count($grouped) + (count($rare) > 0 ? 1 : 0);
            $itemCount = array_sum(array_map('count', $grouped)) + count($rare);

            return ['levels' => $levelCount, 'items' => $itemCount];
        }

        return DB::transaction(function () use ($name, $imageUrl, $categoryId, $grouped, $rare, $force): array {
            $gameCase = GameCase::firstOrCreate(
                ['name' => $name],
                [
                    'image_url' => $imageUrl,
                    'price' => '0.00',
                    'category_id' => $categoryId,
                    'sort_order' => 0,
                    'is_active' => false,
                    'is_featured_on_home' => false,
                    'is_manual_admin_case' => false,
                ],
            );

            $gameCase->forceFill(['is_manual_admin_case' => false])->save();

            if (! $gameCase->wasRecentlyCreated && ! $force) {
                // Кейс уже существует и --force не передан — пропускаем
                $existingLevels = $gameCase->levels()->count();
                $existingItems = CaseItem::whereIn('case_level_id', $gameCase->levels()->pluck('id'))->count();

                return ['levels' => $existingLevels, 'items' => $existingItems];
            }

            if ($force && ! $gameCase->wasRecentlyCreated) {
                // Пересоздаём уровни и предметы
                $gameCase->levels()->delete();
                // Обновим image_url если изменилось
                if ($imageUrl !== null) {
                    $gameCase->update(['image_url' => $imageUrl]);
                }
            }

            $levelsCreated = 0;
            $itemsCreated = 0;

            // Нормализуем шансы для имеющихся уровней
            $normalizedLevels = $this->normalizeLevels($grouped, count($rare) > 0);

            foreach ($normalizedLevels as $levelDef) {
                $level = CaseLevel::create([
                    'case_id' => $gameCase->id,
                    'level' => $levelDef['level'],
                    'name' => $levelDef['name'],
                    'chance' => $levelDef['chance'],
                ]);
                $levelsCreated++;

                foreach ($levelDef['items'] as $item) {
                    $this->createCaseItem($level->id, $item, $levelDef['rarity']);
                    $itemsCreated++;
                }
            }

            // Уровень 5: rare special (ножи, перчатки)
            if (count($rare) > 0) {
                $rareLevel = CaseLevel::create([
                    'case_id' => $gameCase->id,
                    'level' => self::RARE_SPECIAL_LEVEL,
                    'name' => self::RARE_SPECIAL_NAME,
                    'chance' => self::RARE_SPECIAL_CHANCE,
                ]);
                $levelsCreated++;

                foreach ($rare as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $this->createCaseItem($rareLevel->id, $item, ItemRarity::Covert->value);
                    $itemsCreated++;
                }
            }

            return ['levels' => $levelsCreated, 'items' => $itemsCreated];
        });
    }

    /**
     * @param  array<int, mixed>  $contains
     * @return array<int, array{level: int, name: string, chance: float, rarity: string, items: list<array<string, mixed>>}>
     */
    private function groupByLevel(array $contains): array
    {
        $grouped = [];

        foreach ($contains as $item) {
            if (! is_array($item)) {
                continue;
            }

            $rarityId = is_array($item['rarity'] ?? null) ? ($item['rarity']['id'] ?? '') : '';
            if (! is_string($rarityId) || $rarityId === '') {
                continue;
            }

            $def = self::RARITY_LEVEL_MAP[$rarityId] ?? null;
            if ($def === null) {
                continue;
            }

            $level = $def['level'];
            if (! isset($grouped[$level])) {
                $grouped[$level] = [
                    'level' => $level,
                    'name' => $def['name'],
                    'chance' => $def['chance'],
                    'rarity' => $def['rarity'],
                    'items' => [],
                ];
            }
            $grouped[$level]['items'][] = $item;
        }

        return array_values($grouped);
    }

    /**
     * Нормализует шансы уровней, чтобы сумма с учётом rare special = 100%.
     *
     * @param  array<int, array{level: int, name: string, chance: float, rarity: string, items: list<array<string, mixed>>}>  $grouped
     * @return array<int, array{level: int, name: string, chance: float, rarity: string, items: list<array<string, mixed>>}>
     */
    private function normalizeLevels(array $grouped, bool $hasRare): array
    {
        if (count($grouped) === 0) {
            return [];
        }

        $rareChance = $hasRare ? self::RARE_SPECIAL_CHANCE : 0.0;
        $rawTotal = array_sum(array_column($grouped, 'chance'));

        if ($rawTotal <= 0) {
            return $grouped;
        }

        $targetTotal = 100.0 - $rareChance;
        $scale = $targetTotal / $rawTotal;

        return array_map(static function (array $level) use ($scale): array {
            $level['chance'] = round($level['chance'] * $scale, 2);

            return $level;
        }, $grouped);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function createCaseItem(int $caseLevelId, array $item, string $rarityFallback): void
    {
        $name = is_string($item['name'] ?? null) ? $item['name'] : '';
        $imageUrl = is_string($item['image'] ?? null) ? $item['image'] : null;
        $externalId = is_string($item['id'] ?? null) ? $item['id'] : null;

        if ($name === '') {
            return;
        }

        $rarityId = is_array($item['rarity'] ?? null) ? ($item['rarity']['id'] ?? '') : '';
        $rarity = $this->mapRarityToValue(is_string($rarityId) ? $rarityId : '') ?? $rarityFallback;

        CaseItem::create([
            'case_level_id' => $caseLevelId,
            'name' => $name,
            'image_url' => $imageUrl,
            'price' => '0.00',
            'wear' => ItemWear::FN->value,
            'rarity' => $rarity,
            'skin_catalog_external_id' => $externalId,
        ]);
    }

    private function mapRarityToValue(string $rarityId): ?string
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

    private function findOrCreateCategory(): int
    {
        return CaseCategory::firstOrCreate(
            ['name' => DemoDataMarkers::IMPORTED_CASE_CATEGORY_NAME],
            ['sort_order' => 0, 'is_visible' => true],
        )->id;
    }
}
