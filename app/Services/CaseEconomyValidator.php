<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CaseLevel;
use App\Models\GameCase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CaseEconomyValidator
{
    /**
     * @param  Collection<int, CaseLevel>  $levels
     */
    public function validate(GameCase $case, Collection $levels): void
    {
        if (! $case->is_active) {
            return;
        }

        if ($levels->isEmpty()) {
            throw ValidationException::withMessages([
                'levels' => 'Для активного кейса добавьте хотя бы один уровень.',
            ]);
        }

        $totalChance = $levels->reduce(
            static fn (string $carry, CaseLevel $level): string => bcadd($carry, (string) $level->chance, 4),
            '0',
        );

        if (bccomp($totalChance, '100', 4) !== 0) {
            throw ValidationException::withMessages([
                'chance' => sprintf('Сумма шансов уровней должна быть 100%%. Сейчас: %s%%.', $totalChance),
            ]);
        }

        $guaranteedLevel = $levels->sortByDesc('level')->first();
        if (! $guaranteedLevel instanceof CaseLevel) {
            throw ValidationException::withMessages([
                'levels' => 'Не удалось определить гарантированный уровень.',
            ]);
        }

        $expectedGuaranteedAmount = bcmul((string) $case->price, '0.5', 2);
        $actualGuaranteedAmount = (string) $guaranteedLevel->prize_amount;

        if (bccomp($actualGuaranteedAmount, $expectedGuaranteedAmount, 2) !== 0) {
            throw ValidationException::withMessages([
                'prize_amount' => sprintf(
                    'Порог гарантированного уровня (уровень %d) должен быть ровно 50%% от цены кейса: %s ₽.',
                    $guaranteedLevel->level,
                    number_format((float) $expectedGuaranteedAmount, 2, '.', ''),
                ),
            ]);
        }
    }
}
