<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\BalanceType;
use App\Enums\TransactionType;
use App\Enums\WithdrawalRequestStatus;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompleteWithdrawalRequest
{
    public function execute(WithdrawalRequest $request, User $admin): void
    {
        if ($request->status !== WithdrawalRequestStatus::Pending) {
            throw new InvalidArgumentException('Завершить можно только заявку в статусе «ожидает».');
        }

        DB::transaction(function () use ($request, $admin): void {
            $lockedRequest = WithdrawalRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->status !== WithdrawalRequestStatus::Pending) {
                throw new InvalidArgumentException('Заявка уже обработана.');
            }

            $user = User::query()->whereKey($lockedRequest->user_id)->lockForUpdate()->firstOrFail();

            $balance = Balance::query()
                ->where('user_id', $user->id)
                ->where('type', BalanceType::Main)
                ->lockForUpdate()
                ->first();

            if ($balance === null) {
                throw new InvalidArgumentException('У пользователя нет основного баланса.');
            }

            $amount = (string) $lockedRequest->amount;

            if (bccomp($balance->amount, $amount, 2) < 0) {
                throw new InvalidArgumentException('Недостаточно средств на основном балансе.');
            }

            $balance->decrement('amount', $amount);

            Transaction::query()->create([
                'user_id' => $user->id,
                'type' => TransactionType::Withdrawal,
                'amount' => bcsub('0', $amount, 2),
                'balance_after' => (string) $balance->fresh()->amount,
                'reference_type' => 'withdrawal_request',
                'reference_id' => $lockedRequest->id,
                'metadata' => ['admin_id' => $admin->id],
            ]);

            $lockedRequest->update([
                'status' => WithdrawalRequestStatus::Completed,
                'processed_at' => now(),
                'processed_by' => $admin->id,
            ]);
        });
    }
}
