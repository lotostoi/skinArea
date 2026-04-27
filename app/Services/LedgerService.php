<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BalanceType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Balance;
use App\Models\Deal;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ledger\Dto\CreateEntryDto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class LedgerService
{
    public function createPending(CreateEntryDto $dto): Transaction
    {
        $key = $dto->getIdempotencyKey();

        if ($key !== null) {
            $existing = Transaction::query()->where('idempotency_key', $key)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $data = [
            'user_id' => $dto->getUserId(),
            'type' => $dto->getType(),
            'status' => TransactionStatus::Pending,
            'balance_type' => $dto->getBalanceType(),
            'amount' => $dto->getAmount(),
            'balance_after' => '0.00',
            'metadata' => $dto->getMetadata(),
            'idempotency_key' => $key,
        ];

        $reference = $dto->getReference();

        if ($reference !== null) {
            $data['reference_type'] = $reference->getMorphClass();
            $data['reference_id'] = $reference->getKey();
        }

        try {
            return Transaction::query()->create($data);
        } catch (QueryException $exception) {
            if ($key === null) {
                throw $exception;
            }

            $existing = Transaction::query()->where('idempotency_key', $key)->first();
            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    public function post(Transaction $transaction): Transaction
    {
        if ($transaction->status !== TransactionStatus::Pending) {
            throw new InvalidArgumentException(
                sprintf('Транзакция #%d не в статусе pending (статус: %s)', $transaction->id, $transaction->status->value),
            );
        }

        return DB::transaction(function () use ($transaction): Transaction {
            $balance = $this->lockOrCreateBalance($transaction->user_id, $transaction->balance_type);
            $amount = (string) $transaction->amount;

            $newAmount = bcadd($balance->amount, $amount, 2);

            if (bccomp($newAmount, '0', 2) < 0) {
                throw new InsufficientBalanceException(
                    $transaction->balance_type === BalanceType::Hold
                        ? 'Недостаточно средств на удержании'
                        : 'Недостаточно средств на балансе',
                );
            }

            $balance->amount = $newAmount;
            $balance->save();

            $transaction->fill([
                'status' => TransactionStatus::Posted,
                'balance_after' => $newAmount,
                'posted_at' => Carbon::now(),
            ])->save();

            return $transaction->refresh();
        });
    }

    public function fail(Transaction $transaction, string $reason): Transaction
    {
        return $this->closePending($transaction, TransactionStatus::Failed, 'failure_reason', $reason);
    }

    public function cancel(Transaction $transaction, string $reason): Transaction
    {
        return $this->closePending($transaction, TransactionStatus::Cancelled, 'cancelled_reason', $reason);
    }

    public function reverse(Transaction $transaction, string $reason): Transaction
    {
        if ($transaction->status !== TransactionStatus::Posted) {
            throw new InvalidArgumentException(
                sprintf('Откатить можно только posted-транзакцию (#%d, статус: %s)', $transaction->id, $transaction->status->value),
            );
        }

        return DB::transaction(function () use ($transaction, $reason): Transaction {
            $balance = $this->lockOrCreateBalance($transaction->user_id, $transaction->balance_type);

            $reverseAmount = bcmul((string) $transaction->amount, '-1', 2);
            $newAmount = bcadd($balance->amount, $reverseAmount, 2);

            if (bccomp($newAmount, '0', 2) < 0) {
                throw new InsufficientBalanceException('Недостаточно средств для отката транзакции');
            }

            $balance->amount = $newAmount;
            $balance->save();

            $metadata = $transaction->metadata ?? [];
            $metadata['reverse_reason'] = $reason;
            $metadata['reverses_transaction_id'] = $transaction->id;

            $reverseTx = Transaction::query()->create([
                'user_id' => $transaction->user_id,
                'type' => $transaction->type,
                'status' => TransactionStatus::Posted,
                'balance_type' => $transaction->balance_type,
                'amount' => $reverseAmount,
                'balance_after' => $newAmount,
                'reference_type' => $transaction->reference_type,
                'reference_id' => $transaction->reference_id,
                'metadata' => $metadata,
                'reverses_transaction_id' => $transaction->id,
                'posted_at' => Carbon::now(),
            ]);

            $originalMetadata = $transaction->metadata ?? [];
            $originalMetadata['reverse_reason'] = $reason;

            $transaction->fill([
                'status' => TransactionStatus::Reversed,
                'reversed_at' => Carbon::now(),
                'metadata' => $originalMetadata,
            ])->save();

            return $reverseTx;
        });
    }

    public function hold(
        User $user,
        string $amount,
        ?Model $reference = null,
        ?array $metadata = null,
    ): void {
        $this->assertPositive($amount);

        DB::transaction(function () use ($user, $amount, $reference, $metadata): void {
            $metaMain = array_merge($metadata ?? [], ['action' => 'hold', 'leg' => 'main']);
            $metaHold = array_merge($metadata ?? [], ['action' => 'hold', 'leg' => 'hold']);

            $mainDto = (new CreateEntryDto)
                ->setUserId($user->id)
                ->setType(TransactionType::Purchase)
                ->setBalanceType(BalanceType::Main)
                ->setAmount('-'.$amount)
                ->setReference($reference)
                ->setMetadata($metaMain);

            $holdDto = (new CreateEntryDto)
                ->setUserId($user->id)
                ->setType(TransactionType::Purchase)
                ->setBalanceType(BalanceType::Hold)
                ->setAmount($amount)
                ->setReference($reference)
                ->setMetadata($metaHold);

            $this->post($this->createPending($mainDto));
            $this->post($this->createPending($holdDto));
        });
    }

    public function release(
        User $user,
        string $amount,
        ?Model $reference = null,
        ?array $metadata = null,
    ): void {
        $this->assertPositive($amount);

        DB::transaction(function () use ($user, $amount, $reference, $metadata): void {
            $metaHold = array_merge($metadata ?? [], ['action' => 'release_hold', 'leg' => 'hold']);
            $metaMain = array_merge($metadata ?? [], ['action' => 'release_hold', 'leg' => 'main']);

            $holdDto = (new CreateEntryDto)
                ->setUserId($user->id)
                ->setType(TransactionType::Purchase)
                ->setBalanceType(BalanceType::Hold)
                ->setAmount('-'.$amount)
                ->setReference($reference)
                ->setMetadata($metaHold);

            $mainDto = (new CreateEntryDto)
                ->setUserId($user->id)
                ->setType(TransactionType::Purchase)
                ->setBalanceType(BalanceType::Main)
                ->setAmount($amount)
                ->setReference($reference)
                ->setMetadata($metaMain);

            $this->post($this->createPending($holdDto));
            $this->post($this->createPending($mainDto));
        });
    }

    public function transferHoldToSeller(Deal $deal): void
    {
        $price = (string) $deal->price;
        $commission = (string) $deal->commission;
        $sellerAmount = bcsub($price, $commission, 2);

        $this->assertPositive($price);

        $platformUserId = $this->resolvePlatformUserId();

        DB::transaction(function () use ($deal, $price, $commission, $sellerAmount, $platformUserId): void {
            $buyerHoldDto = (new CreateEntryDto)
                ->setUserId((int) $deal->buyer_id)
                ->setType(TransactionType::Purchase)
                ->setBalanceType(BalanceType::Hold)
                ->setAmount('-'.$price)
                ->setReference($deal)
                ->setMetadata(['action' => 'deal_settle', 'leg' => 'buyer_hold']);

            $sellerMainDto = (new CreateEntryDto)
                ->setUserId((int) $deal->seller_id)
                ->setType(TransactionType::Sale)
                ->setBalanceType(BalanceType::Main)
                ->setAmount($sellerAmount)
                ->setReference($deal)
                ->setMetadata(['action' => 'deal_settle', 'leg' => 'seller', 'commission' => $commission]);

            $this->post($this->createPending($buyerHoldDto));
            $this->post($this->createPending($sellerMainDto));

            if ($platformUserId > 0 && bccomp($commission, '0', 2) > 0) {
                $platformDto = (new CreateEntryDto)
                    ->setUserId($platformUserId)
                    ->setType(TransactionType::Sale)
                    ->setBalanceType(BalanceType::Main)
                    ->setAmount($commission)
                    ->setReference($deal)
                    ->setMetadata(['action' => 'deal_settle', 'leg' => 'platform_commission']);

                $this->post($this->createPending($platformDto));
            }
        });
    }

    private function closePending(
        Transaction $transaction,
        TransactionStatus $status,
        string $metaKey,
        string $reason,
    ): Transaction {
        if ($transaction->status !== TransactionStatus::Pending) {
            throw new InvalidArgumentException(
                sprintf('Транзакция #%d не в статусе pending (статус: %s)', $transaction->id, $transaction->status->value),
            );
        }

        $metadata = $transaction->metadata ?? [];
        $metadata[$metaKey] = $reason;

        $transaction->fill([
            'status' => $status,
            'metadata' => $metadata,
        ])->save();

        return $transaction->refresh();
    }

    private function lockOrCreateBalance(int $userId, BalanceType $type): Balance
    {
        $balance = Balance::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->lockForUpdate()
            ->first();

        if ($balance !== null) {
            return $balance;
        }

        Balance::query()->insertOrIgnore([
            'user_id' => $userId,
            'type' => $type->value,
            'amount' => 0,
            'updated_at' => Carbon::now(),
        ]);

        $balance = Balance::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->lockForUpdate()
            ->first();

        if ($balance === null) {
            throw new RuntimeException('Не удалось создать запись баланса');
        }

        return $balance;
    }

    private function resolvePlatformUserId(): int
    {
        $configured = config('skinsarena.platform.user_id');

        if ($configured !== null) {
            return (int) $configured;
        }

        $steamId = config('skinsarena.platform.steam_id');

        if ($steamId === null) {
            return 0;
        }

        $user = User::query()->where('steam_id', (string) $steamId)->first();

        return $user?->id ?? 0;
    }

    private function assertPositive(string $amount): void
    {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new InvalidArgumentException('Сумма операции должна быть положительной');
        }
    }
}
