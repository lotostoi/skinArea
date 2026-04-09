<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncSkinCatalogJob;
use App\Services\SkinCatalogSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncSkinCatalogCommand extends Command
{
    protected $signature = 'skins:catalog-sync
                            {--dry-run : Только разобрать ответ, без записи в БД}
                            {--limit= : Максимум записей (для тестов)}
                            {--queue : Поставить задачу в очередь вместо синхронного запуска}';

    protected $description = 'Загрузить каталог скинов CS2 из внешнего JSON и upsert в skin_catalog_items';

    public function handle(SkinCatalogSyncService $service): int
    {
        if ($this->option('queue')) {
            SyncSkinCatalogJob::dispatch();
            $this->info('Задача SyncSkinCatalogJob поставлена в очередь.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limitOpt = $this->option('limit');
        $limit = $limitOpt !== null && $limitOpt !== '' ? (int) $limitOpt : null;

        $this->info('Источник: '.config('skinsarena.skin_catalog.source_url'));

        try {
            $result = $service->syncFromConfiguredSource($limit, $dryRun);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Синхронизировано записей: '.$result['synced']);
        $this->info('Пропущено строк: '.$result['skipped']);
        if ($dryRun) {
            $this->warn('Режим dry-run: в БД ничего не записано.');
        }

        return self::SUCCESS;
    }
}
