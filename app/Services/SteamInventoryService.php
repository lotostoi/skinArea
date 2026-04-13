<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\SteamInventoryAssetDto;
use App\DTOs\SteamInventoryPageResult;
use App\Enums\ItemCategory;
use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Exceptions\SteamInventoryFetchException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SteamInventoryService
{
    public function fetchPageForSteamId(string $steamId64, ?bool $onlyTradable = null): SteamInventoryPageResult
    {
        $payload = $this->fetchAndValidatePayload($steamId64);
        $filterTradable = $onlyTradable ?? (bool) config('skinsarena.steam_inventory.only_tradable');
        $items = $this->mapPayloadToDtos($payload, $filterTradable);
        $assets = $payload['assets'] ?? [];
        $rawCount = is_array($assets) ? count($assets) : 0;
        $total = $payload['total_inventory_count'] ?? null;
        $steamTotal = is_numeric($total) ? (int) $total : null;

        return new SteamInventoryPageResult(
            items: $items,
            steamTotalInventoryCount: $steamTotal,
            rawAssetCount: $rawCount,
        );
    }

    /**
     * @return list<SteamInventoryAssetDto>
     */
    public function fetchForSteamId(string $steamId64, ?bool $onlyTradable = null): array
    {
        return $this->fetchPageForSteamId($steamId64, $onlyTradable)->items;
    }

    public function findAsset(string $steamId64, string $assetId): ?SteamInventoryAssetDto
    {
        $payload = $this->fetchAndValidatePayload($steamId64);
        foreach ($this->mapPayloadToDtos($payload, true) as $dto) {
            if ($dto->assetId === $assetId) {
                return $dto;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchAndValidatePayload(string $steamId64): array
    {
        $payload = $this->fetchAllPages($steamId64);

        if (($payload['success'] ?? false) !== true && ($payload['success'] ?? null) !== 1) {
            $error = is_string($payload['Error'] ?? null) ? $payload['Error'] : null;
            if ($error !== null && str_contains(mb_strtolower($error), 'private')) {
                throw new SteamInventoryFetchException('Инвентарь Steam скрыт. Сделайте инвентарь публичным в настройках конфиденциальности Steam.');
            }

            throw new SteamInventoryFetchException('Не удалось загрузить инвентарь Steam. Проверьте, что профиль и инвентарь публичные.');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<SteamInventoryAssetDto>
     */
    private function mapPayloadToDtos(array $payload, bool $onlyTradable): array
    {
        $assets = $payload['assets'] ?? [];
        $descriptions = $payload['descriptions'] ?? [];
        if (! is_array($assets) || ! is_array($descriptions)) {
            return [];
        }

        $descMap = $this->buildDescriptionMap($descriptions);

        $out = [];
        foreach ($assets as $asset) {
            if (! is_array($asset)) {
                continue;
            }
            $assetId = isset($asset['assetid']) ? (string) $asset['assetid'] : '';
            $classId = isset($asset['classid']) ? (string) $asset['classid'] : '';
            $instanceId = $this->normalizeInstanceId(
                isset($asset['instanceid']) ? (string) $asset['instanceid'] : '0',
            );
            if ($assetId === '' || $classId === '') {
                continue;
            }
            $key = $this->descriptionKey($classId, $instanceId);
            $desc = $descMap[$key] ?? $descMap[$this->descriptionKey($classId, '0')] ?? null;
            if ($desc === null) {
                continue;
            }

            $isTradable = (int) ($desc['tradable'] ?? 0) === 1;
            if ($onlyTradable && ! $isTradable) {
                continue;
            }

            $name = $this->resolveItemName($desc, $classId);

            $iconUrl = isset($desc['icon_url']) ? (string) $desc['icon_url'] : '';
            $imageBase = rtrim((string) config('skinsarena.steam_inventory.economy_image_base_url'), '/').'/';
            $fullImage = $iconUrl !== '' ? $imageBase.$iconUrl : null;

            $tags = $desc['tags'] ?? [];
            $tags = is_array($tags) ? $tags : [];

            $out[] = new SteamInventoryAssetDto(
                assetId: $assetId,
                name: $name,
                imageUrl: $fullImage,
                wear: $this->mapWear($tags),
                floatValue: $this->extractFloat($desc),
                rarity: $this->mapRarity($tags),
                category: $this->mapCategory($tags),
                classId: $classId,
                instanceId: $instanceId,
                tradable: $isTradable,
            );
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $desc
     */
    private function resolveItemName(array $desc, string $classId): string
    {
        foreach (['market_hash_name', 'market_name', 'name'] as $key) {
            $raw = $desc[$key] ?? null;
            if (! is_string($raw) && ! is_numeric($raw)) {
                continue;
            }
            $s = trim(html_entity_decode(strip_tags((string) $raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($s !== '') {
                return $s;
            }
        }

        $type = $desc['type'] ?? null;
        if (is_string($type) || is_numeric($type)) {
            $s = trim(html_entity_decode(strip_tags((string) $type), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($s !== '') {
                return $s;
            }
        }

        return 'Предмет #'.$classId;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchAllPages(string $steamId64): array
    {
        $appId = (int) config('skinsarena.steam_inventory.app_id');
        $contextId = (int) config('skinsarena.steam_inventory.context_id');
        $count = (int) config('skinsarena.steam_inventory.per_request_count');
        $maxPages = (int) config('skinsarena.steam_inventory.max_pages');
        $timeout = (int) config('skinsarena.steam_inventory.http_timeout_seconds');
        $userAgent = (string) config('skinsarena.steam_inventory.user_agent');

        $merged = [
            'success' => false,
            'assets' => [],
            'descriptions' => [],
        ];

        $startAssetId = null;
        for ($page = 0; $page < $maxPages; $page++) {
            $query = [
                'l' => 'english',
                'count' => $count,
            ];
            if ($startAssetId !== null) {
                $query['start_assetid'] = $startAssetId;
            }

            $response = $this->requestSteamInventoryPage($steamId64, $appId, $contextId, $query, $timeout, $userAgent);

            /** @var array<string, mixed> $json */
            $json = $response->json() ?? [];

            if (($json['success'] ?? false) !== true && ($json['success'] ?? null) !== 1) {
                return $json;
            }

            $merged['success'] = true;
            if (! array_key_exists('total_inventory_count', $merged) && array_key_exists('total_inventory_count', $json)) {
                $merged['total_inventory_count'] = $json['total_inventory_count'];
            }
            $assets = $json['assets'] ?? [];
            $descs = $json['descriptions'] ?? [];
            if (is_array($assets)) {
                /** @var list<mixed> $a */
                $a = $assets;
                foreach ($a as $item) {
                    $merged['assets'][] = $item;
                }
            }
            if (is_array($descs)) {
                /** @var list<mixed> $d */
                $d = $descs;
                foreach ($d as $item) {
                    $merged['descriptions'][] = $item;
                }
            }

            if (empty($json['more_items'])) {
                break;
            }

            $next = $json['last_assetid'] ?? null;
            if ($next === null || $next === '') {
                break;
            }
            $startAssetId = (string) $next;
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function requestSteamInventoryPage(
        string $steamId64,
        int $appId,
        int $contextId,
        array $query,
        int $timeout,
        string $userAgent,
    ): Response {
        $urls = $this->inventoryPageUrls($steamId64, $appId, $contextId);
        $lastStatus = null;
        foreach ($urls as $url) {
            $response = $this->sendSteamInventoryGet($url, $query, $steamId64, $timeout, $userAgent);
            $lastStatus = $response->status();
            if ($lastStatus === 429) {
                throw new SteamInventoryFetchException('Steam временно ограничил запросы. Попробуйте через минуту.');
            }
            if ($response->successful()) {
                return $response;
            }
        }

        if ($lastStatus === 403) {
            throw new SteamInventoryFetchException(
                'Steam вернул 403 на все варианты запроса к инвентарю. Так бывает с IP датацентра или из Docker. Варианты: задать в .env SKINSARENA_STEAM_INVENTORY_HTTP_PROXY (резидентский прокси), или выполнять запрос к Steam с машины с «домашним» IP.',
            );
        }

        if ($lastStatus === 401) {
            throw new SteamInventoryFetchException(
                'Steam вернул 401 (доступ к JSON инвентаря отклонён). Это не из‑за trade URL: запрос идёт по Steam ID. Частая причина — IP сервера/Docker. Попробуйте SKINSARENA_STEAM_INVENTORY_HTTP_PROXY в .env или запрос с другого IP.',
            );
        }

        throw new SteamInventoryFetchException(
            'Не удалось получить ответ Steam (HTTP '.($lastStatus ?? '—').'). Повторите позже.',
        );
    }

    /**
     * @return list<string>
     */
    private function inventoryPageUrls(string $steamId64, int $appId, int $contextId): array
    {
        return [
            sprintf(
                'https://steamcommunity.com/profiles/%s/inventory/json/%d/%d',
                $steamId64,
                $appId,
                $contextId,
            ),
            sprintf(
                'https://steamcommunity.com/inventory/%s/%d/%d',
                $steamId64,
                $appId,
                $contextId,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function sendSteamInventoryGet(
        string $url,
        array $query,
        string $steamId64,
        int $timeout,
        string $userAgent,
    ): Response {
        $headers = [
            'User-Agent' => $userAgent,
            'Accept' => 'application/json, text/javascript, */*;q=0.1',
            'Accept-Language' => 'en-US,en;q=0.9,ru;q=0.8',
            'Referer' => sprintf('https://steamcommunity.com/profiles/%s/inventory/', $steamId64),
            'Origin' => 'https://steamcommunity.com',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-site',
        ];

        $pending = Http::timeout($timeout)->withHeaders($headers);
        $proxy = config('skinsarena.steam_inventory.http_proxy');
        if (is_string($proxy) && $proxy !== '') {
            $pending = $pending->withOptions(['proxy' => $proxy]);
        }

        return $pending->get($url, $query);
    }

    /**
     * @param  list<mixed>  $descriptions
     * @return array<string, array<string, mixed>>
     */
    private function buildDescriptionMap(array $descriptions): array
    {
        $map = [];
        foreach ($descriptions as $row) {
            if (! is_array($row)) {
                continue;
            }
            $classId = isset($row['classid']) ? (string) $row['classid'] : '';
            $instanceId = $this->normalizeInstanceId(
                isset($row['instanceid']) ? (string) $row['instanceid'] : '0',
            );
            if ($classId === '') {
                continue;
            }
            $map[$this->descriptionKey($classId, $instanceId)] = $row;
        }

        return $map;
    }

    private function normalizeInstanceId(string $instanceId): string
    {
        return $instanceId === '' ? '0' : $instanceId;
    }

    private function descriptionKey(string $classId, string $instanceId): string
    {
        return $classId.'_'.$this->normalizeInstanceId($instanceId);
    }

    /**
     * @param  list<mixed>  $tags
     */
    private function mapWear(array $tags): ItemWear
    {
        foreach ($tags as $tag) {
            if (! is_array($tag)) {
                continue;
            }
            $cat = isset($tag['category']) ? (string) $tag['category'] : '';
            if ($cat !== 'Exterior' && $cat !== 'WearCategory') {
                continue;
            }
            $internal = isset($tag['internal_name']) ? (string) $tag['internal_name'] : '';
            $localized = isset($tag['localized_tag_name']) ? mb_strtolower((string) $tag['localized_tag_name']) : '';

            if (preg_match('/WearCategory(\d)/', $internal, $m) === 1) {
                return match ((int) $m[1]) {
                    0 => ItemWear::FN,
                    1 => ItemWear::MW,
                    2 => ItemWear::FT,
                    3 => ItemWear::WW,
                    4 => ItemWear::BS,
                    default => ItemWear::FT,
                };
            }

            return match (true) {
                str_contains($internal, 'WearCategoryFN') => ItemWear::FN,
                str_contains($internal, 'WearCategoryMW') => ItemWear::MW,
                str_contains($internal, 'WearCategoryFT') => ItemWear::FT,
                str_contains($internal, 'WearCategoryWW') => ItemWear::WW,
                str_contains($internal, 'WearCategoryBS') => ItemWear::BS,
                str_contains($localized, 'factory new') => ItemWear::FN,
                str_contains($localized, 'minimal wear') => ItemWear::MW,
                str_contains($localized, 'field-tested') => ItemWear::FT,
                str_contains($localized, 'well-worn') => ItemWear::WW,
                str_contains($localized, 'battle-scarred') => ItemWear::BS,
                default => ItemWear::FT,
            };
        }

        return ItemWear::FT;
    }

    /**
     * @param  list<mixed>  $tags
     */
    private function mapRarity(array $tags): ItemRarity
    {
        foreach ($tags as $tag) {
            if (! is_array($tag)) {
                continue;
            }
            $cat = isset($tag['category']) ? (string) $tag['category'] : '';
            if ($cat !== 'Rarity') {
                continue;
            }
            $localized = isset($tag['localized_tag_name']) ? mb_strtolower((string) $tag['localized_tag_name']) : '';

            return match (true) {
                str_contains($localized, 'contraband') => ItemRarity::Contraband,
                str_contains($localized, 'covert') => ItemRarity::Covert,
                str_contains($localized, 'classified') => ItemRarity::Classified,
                str_contains($localized, 'restricted') => ItemRarity::Restricted,
                str_contains($localized, 'mil-spec') => ItemRarity::MilSpec,
                str_contains($localized, 'industrial') => ItemRarity::IndustrialGrade,
                str_contains($localized, 'consumer') => ItemRarity::ConsumerGrade,
                str_contains($localized, 'extraordinary') => ItemRarity::Covert,
                default => ItemRarity::MilSpec,
            };
        }

        return ItemRarity::MilSpec;
    }

    /**
     * @param  list<mixed>  $tags
     */
    private function mapCategory(array $tags): ItemCategory
    {
        foreach ($tags as $tag) {
            if (! is_array($tag)) {
                continue;
            }
            $cat = isset($tag['category']) ? (string) $tag['category'] : '';
            if ($cat !== 'Type' && $cat !== 'Weapon') {
                continue;
            }
            $localized = isset($tag['localized_tag_name']) ? mb_strtolower((string) $tag['localized_tag_name']) : '';

            return match (true) {
                str_contains($localized, 'knife') => ItemCategory::Knives,
                str_contains($localized, 'gloves') => ItemCategory::Gloves,
                str_contains($localized, 'pistol') => ItemCategory::Pistols,
                str_contains($localized, 'rifle') => ItemCategory::Rifles,
                str_contains($localized, 'sniper') => ItemCategory::Rifles,
                str_contains($localized, 'smg') => ItemCategory::SMGs,
                str_contains($localized, 'shotgun') => ItemCategory::Heavy,
                str_contains($localized, 'machinegun') => ItemCategory::Heavy,
                default => ItemCategory::Other,
            };
        }

        return ItemCategory::Other;
    }

    /**
     * @param  array<string, mixed>  $desc
     */
    private function extractFloat(array $desc): ?string
    {
        $props = $desc['asset_properties'] ?? null;
        if (is_array($props)) {
            foreach ($props as $prop) {
                if (! is_array($prop)) {
                    continue;
                }
                if (($prop['type'] ?? '') === 'float' && isset($prop['float_value'])) {
                    return (string) $prop['float_value'];
                }
            }
        }

        $nested = $desc['descriptions'] ?? [];
        if (! is_array($nested)) {
            return null;
        }

        foreach ($nested as $row) {
            if (! is_array($row)) {
                continue;
            }
            $value = isset($row['value']) ? (string) $row['value'] : '';
            if (preg_match('/(\d+\.\d{4,})/', $value, $m) === 1) {
                return $m[1];
            }
        }

        return null;
    }
}
