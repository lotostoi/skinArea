<?php

declare(strict_types=1);

namespace App\Actions\Cases;

use App\Enums\CaseOpeningStatus;
use App\Models\CaseOpening;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawCaseOpeningAction
{
    public function execute(User $user, CaseOpening $opening, ?string $tradeUrl): CaseOpening
    {
        return DB::transaction(function () use ($user, $opening, $tradeUrl): CaseOpening {
            if ($opening->user_id !== $user->id) {
                throw new AuthorizationException('Этот предмет не принадлежит вам.');
            }

            if ($opening->status !== CaseOpeningStatus::InInventory) {
                throw ValidationException::withMessages([
                    'case_opening' => 'Вывод доступен только для предметов со статусом «В инвентаре».',
                ]);
            }

            $effectiveTradeUrl = trim((string) ($tradeUrl ?? $user->trade_url));
            if ($effectiveTradeUrl === '') {
                throw ValidationException::withMessages([
                    'trade_url' => 'Укажите ссылку на обмен Steam в профиле или передайте trade_url в запросе.',
                ]);
            }

            if ($tradeUrl !== null && trim($tradeUrl) !== '' && $user->trade_url !== $tradeUrl) {
                $user->forceFill(['trade_url' => trim($tradeUrl)])->save();
            }

            $opening->update([
                'status' => CaseOpeningStatus::Withdrawn,
            ]);

            return $opening->fresh()->load(['caseItem', 'gameCase']);
        });
    }
}
