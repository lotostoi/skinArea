<?php

declare(strict_types=1);

namespace App\Services\Prices;

use App\Contracts\SkinPriceProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SteamPriceProvider implements SkinPriceProviderInterface
{
    private const BASE_URL = 'https://steamcommunity.com/market/priceoverview/';

    private const APP_ID = 730;

    /**
     * Получает цены по одному — Steam не предоставляет bulk-эндпоинт.
     * Вызывать в контексте очереди с задержкой между запросами.
     *
     * {@inheritDoc}
     */
    public function getPrices(array $marketHashNames): array
    {
        $result = [];
        $timeout = (int) config('skinsarena.skin_prices.steam.http_timeout_seconds', 10);
        $currency = (int) config('skinsarena.skin_prices.steam.currency', 1);
        $delayMs = (int) config('skinsarena.skin_prices.steam.rate_limit_delay_ms', 3000);

        foreach ($marketHashNames as $i => $hashName) {
            if ($i > 0) {
                // Соблюдаем лимит Steam: ~20 req/min = 3 сек между запросами
                usleep($delayMs * 1000);
            }

            try {
                $response = Http::timeout($timeout)
                    ->withHeaders(['User-Agent' => 'SkinsArena/1.0'])
                    ->get(self::BASE_URL, [
                        'appid' => self::APP_ID,
                        'currency' => $currency,
                        'market_hash_name' => $hashName,
                    ]);

                if (! $response->successful()) {
                    $result[$hashName] = null;

                    continue;
                }

                /** @var array<string, mixed> $data */
                $data = $response->json() ?? [];

                if (($data['success'] ?? false) !== true) {
                    $result[$hashName] = null;

                    continue;
                }

                $price = $this->parsePrice((string) ($data['lowest_price'] ?? ''));
                $result[$hashName] = $price;
            } catch (Throwable $e) {
                Log::warning('skin_price.steam_fetch_failed', [
                    'market_hash_name' => $hashName,
                    'message' => $e->getMessage(),
                ]);
                $result[$hashName] = null;
            }
        }

        return $result;
    }

    private function parsePrice(string $raw): ?float
    {
        if ($raw === '') {
            return null;
        }

        // Убираем символы валют и пробелы: "$6.91" → "6.91", "6,91 $" → "6.91"
        $cleaned = preg_replace('/[^0-9.,]/', '', $raw);
        if ($cleaned === null || $cleaned === '') {
            return null;
        }

        // Нормализуем разделители: "6,91" → "6.91"
        $normalized = str_replace(',', '.', $cleaned);
        $float = filter_var($normalized, FILTER_VALIDATE_FLOAT);

        return $float !== false ? (float) $float : null;
    }
}
