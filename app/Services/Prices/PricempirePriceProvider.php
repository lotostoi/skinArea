<?php

declare(strict_types=1);

namespace App\Services\Prices;

use App\Contracts\SkinPriceProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PricempirePriceProvider implements SkinPriceProviderInterface
{
    private const API_URL = 'https://api.pricempire.com/v4/paid/items/prices';

    /**
     * Один HTTP-запрос возвращает цены на все предметы.
     * Фильтруем результат по переданным именам на нашей стороне.
     *
     * {@inheritDoc}
     */
    public function getPrices(array $marketHashNames): array
    {
        $apiKey = (string) config('skinsarena.skin_prices.pricempire.api_key', '');
        $timeout = (int) config('skinsarena.skin_prices.pricempire.http_timeout_seconds', 30);
        $currency = (string) config('skinsarena.skin_prices.pricempire.currency', 'USD');
        $sources = (string) config('skinsarena.skin_prices.pricempire.sources', 'buff163,steam');

        if ($apiKey === '') {
            throw new \RuntimeException('Не задан PRICEMPIRE_API_KEY в .env.');
        }

        try {
            $response = Http::timeout($timeout)
                ->withToken($apiKey)
                ->get(self::API_URL, [
                    'app_id' => 730,
                    'sources' => $sources,
                    'currency' => $currency,
                    'type' => 'skin,gloves',
                ]);

            $response->throw();

            /** @var list<array<string, mixed>> $items */
            $items = $response->json() ?? [];
        } catch (Throwable $e) {
            Log::error('skin_price.pricempire_fetch_failed', ['message' => $e->getMessage()]);
            throw $e;
        }

        // Индексируем по market_hash_name
        $priceMap = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = $item['market_hash_name'] ?? null;
            if (! is_string($name) || $name === '') {
                continue;
            }

            // Берём первую ненулевую цену из prices[]
            $price = $this->extractBestPrice($item['prices'] ?? []);
            $priceMap[$name] = $price;
        }

        // Возвращаем только запрошенные имена
        $result = [];
        foreach ($marketHashNames as $hashName) {
            $result[$hashName] = $priceMap[$hashName] ?? null;
        }

        return $result;
    }

    private function extractBestPrice(mixed $prices): ?float
    {
        if (! is_array($prices)) {
            return null;
        }

        foreach ($prices as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $price = $entry['price'] ?? null;
            if (is_numeric($price) && (float) $price > 0) {
                return (float) $price;
            }
        }

        return null;
    }
}
