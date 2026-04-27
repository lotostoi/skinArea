<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FundAdjustmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'fundAdjustments';

    protected static ?string $title = 'Лог корректировок фонда';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(static function (string $state): string {
                        return match ($state) {
                            'manual' => 'Ручная',
                            'daily_drain' => 'Ежедневное списание',
                            default => $state,
                        };
                    }),

                TextColumn::make('amount')
                    ->label('Сумма, ₽')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('admin.username')
                    ->label('Администратор')
                    ->placeholder('Система')
                    ->toggleable(),

                TextColumn::make('comment')
                    ->label('Комментарий')
                    ->limit(120)
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
