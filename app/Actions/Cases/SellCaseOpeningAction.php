<?php

declare(strict_types=1);

namespace App\Actions\Cases;

use App\Enums\BalanceType;
use App\Enums\CaseOpeningStatus;
use App\Enums\TransactionType;
use App\Models\CaseOpening;
use App\Models\User;
use App\Services\Ledger\Dto\CreateEntryDto;
use App\Services\LedgerService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SellCaseOpeningAction
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function execute(User $user, CaseOpening $opening): CaseOpening
    {
        return DB::transaction(function () use ($user, $opening): CaseOpening {
            if ($opening->user_id !== $user->id) {
                throw new AuthorizationException('Этот предмет не принадлежит вам.');
            }

            if ($opening->status !== CaseOpeningStatus::InInventory) {
                throw new InvalidArgumentException(
                    sprintf('Невозможно продать предмет со статусом «%s».', $opening->status->value),
                );
            }

            $sellAmount = (string) $opening->won_item_price;

            $dto = (new CreateEntryDto)
                ->setUserId($user->id)
                ->setType(TransactionType::CaseSell)
                ->setBalanceType(BalanceType::Main)
                ->setAmount($sellAmount)
                ->setReference($opening)
                ->setIdempotencyKey('case-sell-'.Str::uuid()->toString())
                ->setMetadata([
                    'case_opening_id' => $opening->id,
                ]);

            $pending = $this->ledger->createPending($dto);
            $this->ledger->post($pending);

            $opening->update(['status' => CaseOpeningStatus::Sold]);

            return $opening->fresh()->load(['caseItem', 'gameCase']);
        });
    }
}
