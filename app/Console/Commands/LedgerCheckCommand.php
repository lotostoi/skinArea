<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BalanceType;
use App\Enums\TransactionStatus;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LedgerCheckCommand extends Command
{
    protected $signature = 'ledger:check';

    protected $description = 'Проверить инвариант: SUM(posted transactions) == balances.amount по каждой паре (user, balance_type)';

    public function handle(): int
    {
        $sums = Transaction::query()
            ->select('user_id', 'balance_type', DB::raw('SUM(amount) as total'))
            ->where('status', TransactionStatus::Posted)
            ->groupBy('user_id', 'balance_type')
            ->get();

        $journal = [];

        foreach ($sums as $row) {
            $userId = (int) $row->user_id;
            $balanceType = $row->balance_type instanceof BalanceType
                ? $row->balance_type
                : BalanceType::from((string) $row->balance_type);
            $journal[$userId.'|'.$balanceType->value] = (string) $row->total;
        }

        $balances = Balance::query()->get();
        $discrepancies = 0;

        foreach ($balances as $balance) {
            $key = $balance->user_id.'|'.$balance->type->value;
            $journalAmount = $journal[$key] ?? '0.00';
            $cached = (string) $balance->amount;

            if (bccomp($cached, $journalAmount, 2) !== 0) {
                $discrepancies++;
                $this->error(sprintf(
                    'РАСХОЖДЕНИЕ: user=%d type=%s cache=%s journal=%s diff=%s',
                    (int) $balance->user_id,
                    $balance->type->value,
                    $cached,
                    $journalAmount,
                    bcsub($cached, $journalAmount, 2),
                ));
            }

            unset($journal[$key]);
        }

        foreach ($journal as $key => $amount) {
            if (bccomp($amount, '0', 2) === 0) {
                continue;
            }
            [$userId, $typeValue] = explode('|', $key);
            $discrepancies++;
            $this->error(sprintf(
                'РАСХОЖДЕНИЕ: user=%s type=%s cache=0.00 journal=%s (нет записи в balances)',
                $userId,
                $typeValue,
                $amount,
            ));
        }

        if ($discrepancies === 0) {
            $this->info('Инвариант сохранён: кеш balances совпадает с posted-журналом.');
            return self::SUCCESS;
        }

        $this->warn(sprintf('Найдено расхождений: %d. Запустите ledger:rebuild-balances.', $discrepancies));
        return self::FAILURE;
    }
}
