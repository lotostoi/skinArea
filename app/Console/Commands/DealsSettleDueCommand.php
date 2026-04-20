<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Market\SettleDeal;
use App\Enums\DealStatus;
use App\Models\Deal;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class DealsSettleDueCommand extends Command
{
    protected $signature = 'deals:settle-due {--limit=100 : Максимум сделок за один запуск}';

    protected $description = 'Завершает сделки в статусе paid, у которых истёк expires_at (эмуляция 7-дневной Steam-защиты)';

    public function handle(SettleDeal $action): int
    {
        $limit = (int) $this->option('limit');

        $deals = Deal::query()
            ->where('status', DealStatus::Paid)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now())
            ->orderBy('expires_at')
            ->limit($limit)
            ->get();

        if ($deals->isEmpty()) {
            $this->info('Сделок для завершения нет.');

            return self::SUCCESS;
        }

        $settled = 0;
        $failed = 0;

        foreach ($deals as $deal) {
            try {
                $action->execute($deal);
                $settled++;
                $this->line(sprintf('Сделка #%d завершена.', $deal->id));
            } catch (Throwable $e) {
                $failed++;
                $this->error(sprintf('Ошибка по сделке #%d: %s', $deal->id, $e->getMessage()));
            }
        }

        $this->info(sprintf('Завершено: %d, с ошибками: %d', $settled, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
