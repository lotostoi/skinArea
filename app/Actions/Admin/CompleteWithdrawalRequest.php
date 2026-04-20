<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\BalanceType;
use App\Enums\TransactionType;
use App\Enums\WithdrawalRequestStatus;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\Ledger\Dto\CreateEntryDto;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompleteWithdrawalRequest
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function execute(WithdrawalRequest $request, User $admin): void
    {
        if ($request->status !== WithdrawalRequestStatus::Pending) {
            throw new InvalidArgumentException('Завершить можно только заявку в статусе «ожидает».');
        }

        DB::transaction(function () use ($request, $admin): void {
            $lockedRequest = WithdrawalRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== WithdrawalRequestStatus::Pending) {
                throw new InvalidArgumentException('Заявка уже обработана.');
            }

            $amount = (string) $lockedRequest->amount;

            $dto = (new CreateEntryDto)
                ->setUserId((int) $lockedRequest->user_id)
                ->setType(TransactionType::Withdrawal)
                ->setBalanceType(BalanceType::Main)
                ->setAmount('-'.$amount)
                ->setReference($lockedRequest)
                ->setIdempotencyKey('withdrawal-'.$lockedRequest->id)
                ->setMetadata(['admin_id' => $admin->id]);

            $pending = $this->ledger->createPending($dto);
            $this->ledger->post($pending);

            $lockedRequest->update([
                'status' => WithdrawalRequestStatus::Completed,
                'processed_at' => now(),
                'processed_by' => $admin->id,
            ]);
        });
    }
}
