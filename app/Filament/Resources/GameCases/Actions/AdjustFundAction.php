<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\Actions;

use App\Models\GameCase;
use App\Services\CasePrizeFundService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class AdjustFundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'adjust_fund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Корректировка фонда')
            ->icon('heroicon-o-adjustments-horizontal')
            ->color('warning')
            ->modalHeading('Корректировка фонда кейса')
            ->modalDescription(
                'Ручная поправка «буфера» экономики кейса: она не списывает и не начисляет деньги на личный баланс игрока, '
                .'а меняет расчётный фонд, от которого зависит доступность дорогих уровней. Используйте при смещении рынка, ошибочном импорте цен или акциях. Запись попадёт в лог корректировок.',
            )
            ->form([
                Select::make('direction')
                    ->label('Направление')
                    ->options([
                        'plus' => 'Пополнить фонд (+)',
                        'minus' => 'Списать из фонда (−)',
                    ])
                    ->required()
                    ->default('plus')
                    ->helperText('Пополнение увеличивает фонд (чаще — чтобы снова открылись дорогие уровни). Списание уменьшает фонд (например, если буфер искусственно завышен).'),

                TextInput::make('amount')
                    ->label('Сумма, ₽')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('₽')
                    ->helperText('Сумма одной операции в рублях. После сохранения пересчитаются доступные уровни и подсказки в блоке статистики выше.'),

                Textarea::make('comment')
                    ->label('Комментарий')
                    ->required()
                    ->rows(2)
                    ->maxLength(500)
                    ->placeholder('Причина корректировки...')
                    ->helperText('Обязательно: кратко для аудита (кто, зачем, тикет, ссылка на переписку). Без осмысленного комментария операцию потом сложно разобрать.'),
            ])
            ->action(function (array $data, GameCase $record): void {
                /** @var CasePrizeFundService $fundService */
                $fundService = app(CasePrizeFundService::class);

                $amount = $data['direction'] === 'minus'
                    ? '-'.$data['amount']
                    : (string) $data['amount'];

                $fundService->createAdjustment(
                    case: $record,
                    type: 'manual',
                    amount: $amount,
                    comment: (string) $data['comment'],
                    adminId: Auth::id(),
                );

                Notification::make()
                    ->title('Корректировка фонда сохранена')
                    ->success()
                    ->send();
            });
    }
}
