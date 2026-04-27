<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\Widgets;

use App\Models\GameCase;
use App\Services\CasePrizeFundService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class CaseFundWidget extends StatsOverviewWidget
{
    public ?GameCase $record = null;

    protected ?string $heading = 'Фонд кейса и доступные уровни';

    protected ?string $description = 'Внутренний учёт кейса (не отдельный баланс платформы). Подсказка к каждой метрике — наведите на «i» рядом с названием.';

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

        $tooltipFund = 'Сколько «буфера» осталось в кейсе: все оплаты открытий минус стоимость выданных призов плюс сумма ручных корректировок из лога ниже. '
            .'От этого числа зависит, какие уровни (кроме гарантированного) допускаются в розыгрыш по правилу фонда.';

        $tooltipSpent = 'Сколько игроки заплатили за открытия этого кейса в сумме (поле cost у открытий). '
            ."Сейчас записей открытий: {$openingsCount}.";

        $tooltipGiven = 'Сколько по прайсу ушло призов пользователям (сумма won_item_price). Чем больше дорогих выпадений, тем сильнее снижается текущий фонд.';

        $tooltipLevels = 'Какие уровни сейчас реально участвуют в выборе приза: гарантированный уровень (с максимальным номером) всегда в списке; остальные подключаются только если фонд не ниже двух пороговых сумм этого уровня (2 × «пороговая стоимость» в карточке уровня).';

        return [
            Stat::make($this->statLabelWithTooltip('Текущий фонд', $tooltipFund), $fundFormatted)
                ->color($fundColor),

            Stat::make($this->statLabelWithTooltip('Всего потрачено', $tooltipSpent), $spentFormatted)
                ->color('info'),

            Stat::make($this->statLabelWithTooltip('Выдано призов', $tooltipGiven), $givenFormatted)
                ->color('warning'),

            Stat::make($this->statLabelWithTooltip('Доступные уровни', $tooltipLevels), $availableLevelsText)
                ->color('primary'),
        ];
    }

    private function statLabelWithTooltip(string $label, string $tooltip): HtmlString
    {
        $title = htmlspecialchars($tooltip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return new HtmlString(
            '<span class="inline-flex flex-wrap items-center gap-x-1.5 gap-y-0.5">'
            .'<span>'.e($label).'</span>'
            .'<span class="ms-0.5 inline-flex h-5 min-w-[1.25rem] cursor-help items-center justify-center self-center rounded-full border border-gray-400 text-[10px] font-bold leading-none text-gray-500 dark:border-gray-500 dark:text-gray-400" title="'.$title.'" aria-label="Пояснение к метрике" role="note" tabindex="0">i</span>'
            .'</span>'
        );
    }
}
