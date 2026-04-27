<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\Widgets;

use App\Models\GameCase;
use App\Services\CasePrizeFundService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CaseFundWidget extends StatsOverviewWidget
{
    public ?GameCase $record = null;

    protected function getStats(): array
    {
        if ($this->record === null) {
            return [];
        }

        $case = $this->record;
        $fundService = app(CasePrizeFundService::class);

        $fundBalance = $fundService->getFundBalance($case);
        $availableLevels = $fundService->getAvailableLevels($case);

        $totalSpent = (string) $case->openings()->sum('cost');
        $totalGiven = (string) $case->openings()->sum('won_item_price');
        $openingsCount = $case->openings()->count();

        $availableLevelsText = $availableLevels->isEmpty()
            ? 'Нет'
            : $availableLevels->pluck('name')->join(', ');

        $fundFormatted = number_format((float) $fundBalance, 2, '.', ' ').' ₽';
        $spentFormatted = number_format((float) $totalSpent, 2, '.', ' ').' ₽';
        $givenFormatted = number_format((float) $totalGiven, 2, '.', ' ').' ₽';

        $fundColor = bccomp($fundBalance, '0', 2) >= 0 ? 'success' : 'danger';

        return [
            Stat::make('Текущий фонд', $fundFormatted)
                ->description('Потрачено − Выдано + Корректировки')
                ->color($fundColor),

            Stat::make('Всего потрачено', $spentFormatted)
                ->description("{$openingsCount} открытий")
                ->color('info'),

            Stat::make('Выдано призов', $givenFormatted)
                ->description('Сумма выигранных предметов')
                ->color('warning'),

            Stat::make('Доступные уровни', $availableLevelsText)
                ->description('Уровни с достаточным фондом')
                ->color('primary'),
        ];
    }
}
