<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BalanceType;
use App\Enums\TransactionStatus;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LedgerRebuildBalancesCommand extends Command
{
    protected $signature = 'ledger:rebuild-balances
        {--user= : ID пользователя для пересчёта (если не указан — пересчитать всем)}
        {--dry-run : Только показать отличия, не обновлять кеш}';

    protected $description = 'Пересчитать кеш balances.amount из posted-журнала transactions';

    public function handle(): int
    {
        $userFilter = $this->option('user');
        $isDryRun = (bool) $this->option('dry-run');

        $query = Transaction::query()
            ->select('user_id', 'balance_type', DB::raw('SUM(amount) as total'))
            ->where('status', TransactionStatus::Posted)
            ->groupBy('user_id', 'balance_type');

        if ($userFilter !== null) {
            $query->where('user_id', (int) $userFilter);
        }

        $sums = $query->get();

        $seenPairs = [];
        $diffs = 0;
        $updates = 0;

        foreach ($sums as $row) {
            $userId = (int) $row->user_id;
            $balanceType = $row->balance_type instanceof BalanceType
                ? $row->balance_type
                : BalanceType::from((string) $row->balance_type);
            $sum = (string) $row->total;

            $seenPairs[$userId.'|'.$balanceType->value] = true;

            $balance = Balance::query()
                ->where('user_id', $userId)
                ->where('type', $balanceType)
                ->first();

            $current = $balance?->amount ?? '0.00';
            $cmp = bccomp((string) $current, $sum, 2);

            if ($cmp !== 0) {
                $diffs++;
                $this->line(sprintf(
                    'user=%d type=%s journal=%s cache=%s',
                    $userId,
                    $balanceType->value,
                    $sum,
                    (string) $current,
                ));

                if (! $isDryRun) {
                    Balance::query()->updateOrCreate(
                        ['user_id' => $userId, 'type' => $balanceType],
                        ['amount' => $sum],
                    );
                    $updates++;
                }
            }
        }

        if (! $isDryRun && $userFilter === null) {
            $extraBalances = Balance::query()
                ->where(function ($q): void {
                    $q->where('amount', '>', 0)
                        ->orWhere('amount', '<', 0);
                })
                ->get();

            foreach ($extraBalances as $balance) {
                $key = $balance->user_id.'|'.$balance->type->value;

                if (isset($seenPairs[$key])) {
                    continue;
                }

                if (bccomp((string) $balance->amount, '0', 2) !== 0) {
                    $this->line(sprintf(
                        'user=%d type=%s cache=%s journal=0.00',
                        (int) $balance->user_id,
                        $balance->type->value,
                        (string) $balance->amount,
                    ));
                    $balance->amount = '0.00';
                    $balance->save();
                    $diffs++;
                    $updates++;
                }
            }
        }

        $this->info(sprintf('Расхождений: %d, обновлено: %d%s', $diffs, $updates, $isDryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }
}
