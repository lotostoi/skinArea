<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transactions\Tables;

use App\Enums\BalanceType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Services\LedgerService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.username')->label('Пользователь')->searchable(),
                TextColumn::make('type')->label('Тип')->badge()->searchable(),
                TextColumn::make('status')->label('Статус')->badge()->color(fn (TransactionStatus $state): string => match ($state) {
                    TransactionStatus::Pending => 'warning',
                    TransactionStatus::Posted => 'success',
                    TransactionStatus::Reversed => 'gray',
                    TransactionStatus::Failed => 'danger',
                    TransactionStatus::Cancelled => 'gray',
                }),
                TextColumn::make('balance_type')->label('Счёт')->badge(),
                TextColumn::make('amount')->label('Сумма')->numeric()->sortable(),
                TextColumn::make('balance_after')->label('Баланс после')->numeric()->sortable(),
                TextColumn::make('reference_type')->label('Ссылка (тип)')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reference_id')->label('Ссылка (id)')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reverses_transaction_id')->label('Откат #')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('posted_at')->label('Проведено')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Создано')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(array_combine(
                        array_map(fn (TransactionStatus $s): string => $s->value, TransactionStatus::cases()),
                        array_map(fn (TransactionStatus $s): string => $s->value, TransactionStatus::cases()),
                    )),
                SelectFilter::make('balance_type')
                    ->label('Счёт')
                    ->options(array_combine(
                        array_map(fn (BalanceType $s): string => $s->value, BalanceType::cases()),
                        array_map(fn (BalanceType $s): string => $s->value, BalanceType::cases()),
                    )),
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(array_combine(
                        array_map(fn (TransactionType $s): string => $s->value, TransactionType::cases()),
                        array_map(fn (TransactionType $s): string => $s->value, TransactionType::cases()),
                    )),
            ])
            ->recordActions([
                Action::make('reverse')
                    ->label('Откатить')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('danger')
                    ->visible(fn (Transaction $record): bool => $record->status === TransactionStatus::Posted)
                    ->schema(fn (Schema $schema): Schema => $schema->components([
                        Textarea::make('reason')
                            ->label('Причина отката')
                            ->required()
                            ->rows(3)
                            ->maxLength(500),
                    ]))
                    ->action(function (Transaction $record, array $data): void {
                        try {
                            app(LedgerService::class)->reverse($record, (string) $data['reason']);
                            Notification::make()
                                ->title('Транзакция откачена')
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Ошибка отката')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([]);
    }
}
