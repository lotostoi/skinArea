<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ItemWear;
use App\Jobs\SyncSkinPriceJob;
use App\Models\CaseItem;
use App\Models\SkinCatalogItem;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncSkinPricesCommand extends Command
{
    protected $signature = 'skins:prices-sync
                            {--batch-size=5 : Сколько скинов в одном Job (только для Steam-провайдера)}
                            {--delay=20 : Задержка между батчами в секундах (только для Steam)}
                            {--only-missing : Обновлять только скины без цены}';

    protected $description = 'Запустить синхронизацию рыночных цен скинов из case_items через очередь';

    public function handle(): int
    {
        $provider = (string) config('skinsarena.skin_prices.provider', 'steam');
        $batchSize = max(1, (int) $this->option('batch-size'));
        $delaySec = max(0, (int) $this->option('delay'));
        $onlyMissing = (bool) $this->option('only-missing');

        $this->info("Провайдер цен: {$provider}");

        // Собираем уникальные external_id из case_items
        /** @var Collection<int, \stdClass> $rows */
        $query = CaseItem::query()
            ->whereNotNull('skin_catalog_external_id')
            ->select('skin_catalog_external_id', 'wear')
            ->distinct();

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->warn('Нет предметов в case_items с skin_catalog_external_id. Запустите skins:cases-import сначала.');

            return self::SUCCESS;
        }

        // Строим market_hash_name: "{name} ({Wear full label})"
        $wearLabels = [
            ItemWear::FN->value => 'Factory New',
            ItemWear::MW->value => 'Minimal Wear',
            ItemWear::FT->value => 'Field-Tested',
            ItemWear::WW->value => 'Well-Worn',
            ItemWear::BS->value => 'Battle-Scarred',
        ];

        $pairs = collect();
        foreach ($rows as $row) {
            $externalId = $row->skin_catalog_external_id;
            $wear = $row->wear instanceof ItemWear ? $row->wear->value : (string) $row->wear;

            $catalogQuery = SkinCatalogItem::where('external_id', $externalId);
            if ($onlyMissing) {
                $catalogQuery->whereNull('market_price');
            }
            $catalogItem = $catalogQuery->first();
            if ($catalogItem === null) {
                continue;
            }

            $wearLabel = $wearLabels[$wear] ?? 'Factory New';
            $marketHashName = "{$catalogItem->name} ({$wearLabel})";

            $pairs->push(['external_id' => $externalId, 'market_hash_name' => $marketHashName]);
        }

        $pairs = $pairs->unique('market_hash_name')->values();
        $total = $pairs->count();

        $this->info("Уникальных скинов для синхронизации: {$total}");

        if ($provider === 'pricempire') {
            // Pricempire: один Job = все скины
            SyncSkinPriceJob::dispatch(
                $pairs->pluck('external_id')->all(),
                $pairs->pluck('market_hash_name')->all(),
            );
            $this->info('Запущен 1 Job (Pricempire, bulk).');
        } else {
            // Steam: один Job = один батч, с задержкой между батчами
            $chunks = $pairs->chunk($batchSize);
            $jobCount = 0;

            foreach ($chunks as $chunk) {
                $delay = $jobCount * $delaySec;

                SyncSkinPriceJob::dispatch(
                    $chunk->pluck('external_id')->all(),
                    $chunk->pluck('market_hash_name')->all(),
                )->delay(now()->addSeconds($delay));

                $jobCount++;
            }

            $estimatedMin = round(($total * $delaySec) / 60, 1);
            $this->info("Запущено Jobs: {$jobCount} (батч по {$batchSize}, задержка {$delaySec}с).");
            $this->info("Ожидаемое время завершения: ~{$estimatedMin} мин.");
        }

        return self::SUCCESS;
    }
}
