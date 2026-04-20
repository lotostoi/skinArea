<?php

declare(strict_types=1);

namespace App\Actions\Balance;

use App\Enums\BalanceType;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ledger\Dto\CreateEntryDto;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessFakeDeposit
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function execute(User $user, string $amount): Transaction
    {
        return DB::transaction(function () use ($user, $amount): Transaction {
            $dto = (new CreateEntryDto)
                ->setUserId($user->id)
                ->setType(TransactionType::Deposit)
                ->setBalanceType(BalanceType::Main)
                ->setAmount($amount)
                ->setIdempotencyKey('fake-deposit-'.Str::uuid()->toString())
                ->setMetadata([
                    'provider' => 'fake',
                    'provider_status' => 'succeeded',
                ]);

            $pending = $this->ledger->createPending($dto);

            return $this->ledger->post($pending);
        });
    }
}
