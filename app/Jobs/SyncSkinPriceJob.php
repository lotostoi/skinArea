<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\SkinPriceProviderInterface;
use App\Models\SkinCatalogItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSkinPriceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * @param  list<string>  $externalIds  ID из skin_catalog_items.external_id
     * @param  list<string>  $marketHashNames  соответствующие market_hash_name для Steam/Pricempire
     */
    public function __construct(
        public readonly array $externalIds,
        public readonly array $marketHashNames,
    ) {}

    public function handle(SkinPriceProviderInterface $provider): void
    {
        try {
            $prices = $provider->getPrices($this->marketHashNames);
        } catch (Throwable $e) {
            Log::error('sync_skin_price.fetch_failed', ['message' => $e->getMessage()]);
            $this->fail($e);

            return;
        }

        foreach ($this->externalIds as $idx => $externalId) {
            $marketHashName = $this->marketHashNames[$idx] ?? null;
            if ($marketHashName === null) {
                continue;
            }

            $price = $prices[$marketHashName] ?? null;
            if ($price === null) {
                continue;
            }

            SkinCatalogItem::where('external_id', $externalId)
                ->update(['market_price' => $price, 'price_synced_at' => now()]);
        }
    }
}
