<?php

declare(strict_types=1);

namespace App\Filament\Resources\WithdrawalRequests\Tables;

use App\Actions\Admin\CompleteWithdrawalRequest;
use App\Actions\Admin\RejectWithdrawalRequest;
use App\Enums\WithdrawalRequestStatus;
use App\Models\WithdrawalRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class WithdrawalRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.username')->label('Пользователь')->searchable(),
                TextColumn::make('amount')->label('Сумма')->numeric()->sortable(),
                TextColumn::make('status')->label('Статус')->badge()->searchable(),
                TextColumn::make('admin_comment')->label('Комментарий')->limit(40)->toggleable(),
                TextColumn::make('processed_at')->label('Обработано')->dateTime()->sortable(),
                TextColumn::make('processor.username')->label('Кем обработано')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Создано')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('Выплатить')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Подтвердите списание с баланса')
                    ->modalDescription('С суммы основного баланса пользователя будет списана заявленная сумма и создана транзакция.')
                    ->visible(fn (WithdrawalRequest $record): bool => $record->status === WithdrawalRequestStatus::Pending)
                    ->action(function (WithdrawalRequest $record): void {
                        try {
                            app(CompleteWithdrawalRequest::class)->execute($record, auth()->user());
                            Notification::make()->title('Заявка отмечена как оплаченная')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Отклонить')
                    ->color('danger')
                    ->form([
                        Textarea::make('comment')->label('Комментарий администратора')->maxLength(2000),
                    ])
                    ->visible(fn (WithdrawalRequest $record): bool => $record->status === WithdrawalRequestStatus::Pending)
                    ->action(function (WithdrawalRequest $record, array $data): void {
                        try {
                            app(RejectWithdrawalRequest::class)->execute(
                                $record,
                                auth()->user(),
                                $data['comment'] ?? null,
                            );
                            Notification::make()->title('Заявка отклонена')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
