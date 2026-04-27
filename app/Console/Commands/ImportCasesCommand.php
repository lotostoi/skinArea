<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CasesImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportCasesCommand extends Command
{
    protected $signature = 'skins:cases-import
                            {--dry-run : Разобрать ответ без записи в БД}
                            {--limit= : Максимум кейсов для обработки}
                            {--filter-name= : Импортировать только кейс с этим именем (частичное совпадение)}
                            {--force : Пересоздать уровни и предметы если кейс уже существует}';

    protected $description = 'Загрузить официальные кейсы CS2 из ByMykel crates.json и создать GameCase/CaseLevel/CaseItem';

    public function handle(CasesImportService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $limitOpt = $this->option('limit');
        $limit = $limitOpt !== null && $limitOpt !== '' ? (int) $limitOpt : null;
        $filterName = $this->option('filter-name') ?: null;

        $this->info('Источник: '.config('skinsarena.skin_catalog.crates_source_url'));

        if ($dryRun) {
            $this->warn('Режим dry-run: в БД ничего не будет записано.');
        }

        if ($force && ! $dryRun) {
            $this->warn('Флаг --force: существующие уровни и предметы будут пересозданы.');
        }

        try {
            $result = $service->importFromConfiguredSource($limit, $filterName, $dryRun, $force);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Кейсов обработано: '.$result['cases']);
        $this->info('Уровней создано:   '.$result['levels']);
        $this->info('Предметов создано: '.$result['items']);
        $this->info('Пропущено записей: '.$result['skipped']);

        if ($dryRun) {
            $this->warn('Режим dry-run: данные не сохранены.');
        }

        return self::SUCCESS;
    }
}
