<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OpeningsRelationManager extends RelationManager
{
    protected static string $relationship = 'openings';

    protected static ?string $title = 'История выдачи призов';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('user.username')
                    ->label('Пользователь')
                    ->searchable(),

                TextColumn::make('caseItem.name')
                    ->label('Выданный предмет')
                    ->toggleable(),

                TextColumn::make('cost')
                    ->label('Потрачено, ₽')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('won_item_price')
                    ->label('Выдано, ₽')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Дата открытия')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
