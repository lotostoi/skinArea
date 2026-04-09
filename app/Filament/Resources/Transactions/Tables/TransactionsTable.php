<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.username')->label('Пользователь')->searchable(),
                TextColumn::make('type')->label('Тип')->badge()->searchable(),
                TextColumn::make('amount')->label('Сумма')->numeric()->sortable(),
                TextColumn::make('balance_after')->label('Баланс после')->numeric()->sortable(),
                TextColumn::make('reference_type')->label('Ссылка (тип)')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reference_id')->label('Ссылка (id)')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Создано')->dateTime()->sortable(),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
