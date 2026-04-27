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

    protected ?string $heading = 'Фонд кейса и доступные уровни';

    protected ?string $description = 'Здесь показано, сколько денег «осталось» в экономике этого кейса после открытий и ручных правок, и какие уровни призов сейчас участвуют в розыгрыше. Эти цифры не списывают баланс платформы отдельно — это учёт внутри кейса для честных шансов.';

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
                ->description(
                    'Сколько «буфера» осталось в кейсе: все оплаты открытий минус стоимость выданных призов плюс сумма ручных корректировок из лога ниже. '
                    .'От этого числа зависит, какие уровни (кроме гарантированного) допускаются в розыгрыш по правилу фонда.',
                )
                ->color($fundColor),

            Stat::make('Всего потрачено', $spentFormatted)
                ->description(
                    "Сколько игроки заплатили за открытия этого кейса в сумме (поле cost у открытий). Сейчас записей открытий: {$openingsCount}.",
                )
                ->color('info'),

            Stat::make('Выдано призов', $givenFormatted)
                ->description(
                    'Сколько по прайсу ушло призов пользователям (сумма won_item_price). Чем больше дорогих выпадений, тем сильнее снижается текущий фонд.',
                )
                ->color('warning'),

            Stat::make('Доступные уровни', $availableLevelsText)
                ->description(
                    'Какие уровни сейчас реально участвуют в выборе приза: гарантированный уровень (с максимальным номером) всегда в списке; остальные подключаются только если фонд не ниже двух пороговых сумм этого уровня (2 × «пороговая стоимость» в карточке уровня).',
                )
                ->color('primary'),
        ];
    }
}
