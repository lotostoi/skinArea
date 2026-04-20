<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Market\CancelDeal;
use App\Models\Deal;
use Illuminate\Console\Command;
use Throwable;

class DealsCancelCommand extends Command
{
    protected $signature = 'deals:cancel {id : ID сделки} {--reason=Отмена администратором : Причина отмены}';

    protected $description = 'Отменить сделку в статусе paid, вернуть деньги покупателю из hold на основной баланс';

    public function handle(CancelDeal $action): int
    {
        $deal = Deal::query()->find((int) $this->argument('id'));

        if ($deal === null) {
            $this->error('Сделка не найдена.');

            return self::FAILURE;
        }

        try {
            $action->execute($deal, (string) $this->option('reason'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Сделка #%d отменена, деньги возвращены покупателю.', $deal->id));

        return self::SUCCESS;
    }
}
