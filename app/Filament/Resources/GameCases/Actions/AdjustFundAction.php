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
            ->form([
                Select::make('direction')
                    ->label('Направление')
                    ->options([
                        'plus' => 'Пополнить фонд (+)',
                        'minus' => 'Списать из фонда (−)',
                    ])
                    ->required()
                    ->default('plus'),

                TextInput::make('amount')
                    ->label('Сумма, ₽')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('₽'),

                Textarea::make('comment')
                    ->label('Комментарий')
                    ->required()
                    ->rows(2)
                    ->maxLength(500)
                    ->placeholder('Причина корректировки...'),
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
