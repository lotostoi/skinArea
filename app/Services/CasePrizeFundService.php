<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CaseFundAdjustment;
use App\Models\CaseLevel;
use App\Models\GameCase;
use Illuminate\Support\Collection;

class CasePrizeFundService
{
    /**
     * Текущий нераспределённый фонд кейса.
     * Фонд = SUM(openings.cost) - SUM(openings.won_item_price) + SUM(adjustments.amount)
     */
    public function getFundBalance(GameCase $case): string
    {
        $spent = (string) $case->openings()->sum('cost');
        $given = (string) $case->openings()->sum('won_item_price');
        $adjustments = (string) $case->fundAdjustments()->sum('amount');

        return bcadd(bcsub($spent, $given, 2), $adjustments, 2);
    }

    /**
     * Уровни, доступные для выпадения с учётом текущего фонда.
     * Уровень с наибольшим номером (гарантированный — самый дешёвый) доступен всегда.
     * Остальные — только если fund >= 2 × prize_amount уровня.
     */
    public function getAvailableLevels(GameCase $case): Collection
    {
        $fundBalance = $this->getFundBalance($case);
        $levels = $case->levels()->with('items')->orderBy('level')->get();

        if ($levels->isEmpty()) {
            return collect();
        }

        $guaranteedLevel = $levels->sortByDesc('level')->first();

        return $levels->filter(function (CaseLevel $level) use ($fundBalance, $guaranteedLevel): bool {
            if ($level->id === $guaranteedLevel->id) {
                return true;
            }

            $prizeAmount = (string) $level->prize_amount;

            if (bccomp($prizeAmount, '0', 2) <= 0) {
                return false;
            }

            $threshold = bcmul($prizeAmount, '2', 2);

            return bccomp($fundBalance, $threshold, 2) >= 0;
        })->values();
    }

    /**
     * Пересчёт вероятностей для доступных уровней.
     * Шансы пересчитываются пропорционально. Гарантированный уровень получает остаток до 100%.
     *
     * @param  Collection<int, CaseLevel>  $availableLevels
     * @return array<int, array{level: CaseLevel, chance: string}>
     */
    public function redistributeChances(Collection $availableLevels, CaseLevel $guaranteedLevel): array
    {
        if ($availableLevels->count() === 1) {
            return [[
                'level' => $guaranteedLevel,
                'chance' => '100.00',
            ]];
        }

        $nonGuaranteed = $availableLevels->filter(fn (CaseLevel $l): bool => $l->id !== $guaranteedLevel->id);

        $sumNonGuaranteedChances = $nonGuaranteed->reduce(
            fn (string $carry, CaseLevel $l): string => bcadd($carry, (string) $l->chance, 4),
            '0',
        );

        $result = [];
        $usedChance = '0';

        foreach ($nonGuaranteed as $level) {
            $redistributed = bcmul((string) $level->chance, '1', 4);
            $result[] = ['level' => $level, 'chance' => $redistributed];
            $usedChance = bcadd($usedChance, $redistributed, 4);
        }

        $guaranteedChance = bcsub('100', $usedChance, 4);

        if (bccomp($guaranteedChance, '0', 4) < 0) {
            $guaranteedChance = '0.0001';
        }

        $result[] = ['level' => $guaranteedLevel, 'chance' => $guaranteedChance];

        return $result;
    }

    /**
     * Создать запись корректировки фонда (ручную или автоматическую).
     */
    public function createAdjustment(
        GameCase $case,
        string $type,
        string $amount,
        ?string $comment = null,
        ?int $adminId = null,
    ): CaseFundAdjustment {
        return CaseFundAdjustment::query()->create([
            'case_id' => $case->id,
            'type' => $type,
            'amount' => $amount,
            'comment' => $comment,
            'admin_id' => $adminId,
        ]);
    }

    /**
     * Ежедневное списание фонда (по умолчанию 5% от текущего фонда).
     */
    public function drainFund(GameCase $case): void
    {
        $fundBalance = $this->getFundBalance($case);

        if (bccomp($fundBalance, '0', 2) <= 0) {
            return;
        }

        $percent = (string) config('skinsarena.cases.daily_drain_percent', 5.0);
        $drainAmount = bcmul($fundBalance, bcdiv($percent, '100', 6), 2);

        if (bccomp($drainAmount, '0', 2) <= 0) {
            return;
        }

        $this->createAdjustment(
            case: $case,
            type: 'daily_drain',
            amount: '-'.$drainAmount,
            comment: sprintf('Автоматическое ежедневное списание %s%% от фонда', $percent),
        );
    }

    /**
     * Получить уровень-гарантированный (с наибольшим номером — самый дешёвый).
     */
    public function getGuaranteedLevel(GameCase $case): ?CaseLevel
    {
        return $case->levels()->orderByDesc('level')->first();
    }
}
