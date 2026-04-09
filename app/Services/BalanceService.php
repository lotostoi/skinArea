<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BalanceType;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Balance;
use App\Models\Deal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    public function charge(
        User $user,
        string $amount,
        TransactionType $type,
        ?Model $reference = null,
        ?array $metadata = null,
    ): Transaction {
        return DB::transaction(function () use ($user, $amount, $type, $reference, $metadata): Transaction {
            $balance = $this->lockBalance($user, BalanceType::Main);

            if (bccomp($balance->amount, $amount, 2) < 0) {
                throw new InsufficientBalanceException;
            }

            $balance->decrement('amount', $amount);

            return $this->recordTransaction(
                user: $user,
                amount: '-'.$amount,
                type: $type,
                balanceAfter: $balance->fresh()->amount,
                reference: $reference,
                metadata: $metadata,
            );
        });
    }

    public function credit(
        User $user,
        string $amount,
        TransactionType $type,
        ?Model $reference = null,
        ?array $metadata = null,
    ): Transaction {
        return DB::transaction(function () use ($user, $amount, $type, $reference, $metadata): Transaction {
            $balance = $this->lockBalance($user, BalanceType::Main);

            $balance->increment('amount', $amount);

            return $this->recordTransaction(
                user: $user,
                amount: $amount,
                type: $type,
                balanceAfter: $balance->fresh()->amount,
                reference: $reference,
                metadata: $metadata,
            );
        });
    }

    public function hold(
        User $user,
        string $amount,
        TransactionType $type,
        ?Model $reference = null,
    ): void {
        DB::transaction(function () use ($user, $amount, $type, $reference): void {
            $main = $this->lockBalance($user, BalanceType::Main);
            $hold = $this->lockBalance($user, BalanceType::Hold);

            if (bccomp($main->amount, $amount, 2) < 0) {
                throw new InsufficientBalanceException;
            }

            $main->decrement('amount', $amount);
            $hold->increment('amount', $amount);

            $this->recordTransaction(
                user: $user,
                amount: '-'.$amount,
                type: $type,
                balanceAfter: $main->fresh()->amount,
                reference: $reference,
                metadata: ['action' => 'hold'],
            );
        });
    }

    public function releaseHold(
        User $user,
        string $amount,
        TransactionType $type,
        ?Model $reference = null,
    ): void {
        DB::transaction(function () use ($user, $amount, $type, $reference): void {
            $hold = $this->lockBalance($user, BalanceType::Hold);
            $main = $this->lockBalance($user, BalanceType::Main);

            if (bccomp($hold->amount, $amount, 2) < 0) {
                throw new InsufficientBalanceException('Недостаточно средств на удержании');
            }

            $hold->decrement('amount', $amount);
            $main->increment('amount', $amount);

            $this->recordTransaction(
                user: $user,
                amount: $amount,
                type: $type,
                balanceAfter: $main->fresh()->amount,
                reference: $reference,
                metadata: ['action' => 'release_hold'],
            );
        });
    }

    public function transferHoldToSeller(Deal $deal): void
    {
        DB::transaction(function () use ($deal): void {
            $buyer = $deal->buyer;
            $seller = $deal->seller;

            $buyerHold = $this->lockBalance($buyer, BalanceType::Hold);
            $sellerMain = $this->lockBalance($seller, BalanceType::Main);

            $totalAmount = $deal->price;
            $commission = $deal->commission;
            $sellerAmount = bcsub($totalAmount, $commission, 2);

            if (bccomp($buyerHold->amount, $totalAmount, 2) < 0) {
                throw new InsufficientBalanceException('Недостаточно средств на удержании покупателя');
            }

            $buyerHold->decrement('amount', $totalAmount);
            $sellerMain->increment('amount', $sellerAmount);

            $this->recordTransaction(
                user: $seller,
                amount: $sellerAmount,
                type: TransactionType::Sale,
                balanceAfter: $sellerMain->fresh()->amount,
                reference: $deal,
                metadata: ['commission' => $commission],
            );
        });
    }

    private function lockBalance(User $user, BalanceType $type): Balance
    {
        return Balance::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function recordTransaction(
        User $user,
        string $amount,
        TransactionType $type,
        string $balanceAfter,
        ?Model $reference = null,
        ?array $metadata = null,
    ): Transaction {
        $data = [
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'metadata' => $metadata,
        ];

        if ($reference !== null) {
            $data['reference_type'] = $reference->getMorphClass();
            $data['reference_id'] = $reference->getKey();
        }

        return Transaction::query()->create($data);
    }
}
