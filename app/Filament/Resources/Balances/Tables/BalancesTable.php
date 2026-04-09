<?php

declare(strict_types=1);

namespace App\Filament\Resources\Balances\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.username')->label('Пользователь')->searchable(),
                TextColumn::make('user.steam_id')->label('Steam ID')->searchable(),
                TextColumn::make('type')->label('Тип')->badge()->searchable(),
                TextColumn::make('amount')->label('Сумма')->numeric()->sortable(),
                TextColumn::make('updated_at')->label('Обновлено')->dateTime()->sortable(),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
