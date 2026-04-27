<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\RelationManagers;

use App\Filament\Resources\CaseLevels\CaseLevelResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LevelsRelationManager extends RelationManager
{
    protected static string $relationship = 'levels';

    protected static ?string $title = 'Шаг 2 — Уровни редкости';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('level')
                    ->label('Номер уровня')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->helperText(
                        'Строка с наибольшим номером — гарантированный уровень (в ТЗ это «5-й»): он всегда участвует в розыгрыше. Его пороговая цена задаётся отдельным правилом — см. подсказку у поля «Пороговая цена».',
                    ),

                TextInput::make('name')
                    ->label('Название уровня')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Например: Армейское, Засекречённое, Нож/Перчатки')
                    ->helperText('Видно пользователю на странице кейса.'),

                TextInput::make('chance')
                    ->label('Шанс выпадения, %')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step('0.0001')
                    ->suffix('%')
                    ->helperText('Сумма шансов всех уровней должна быть ровно 100%. Типичные значения: Уровень 1 — 70%, 2 — 20%, 3 — 7%, 4 — 2%, 5 — 1%.'),

                TextInput::make('prize_amount')
                    ->label('Пороговая цена уровня, ₽')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('₽')
                    ->default(0)
                    ->helperText(
                        'Одно поле — два правила. (1) Гарантированный уровень (наибольший номер): при активном кейсе значение должно быть ровно половине «Цены открытия» кейса на вкладке «Основное» — иначе сохранение не пройдёт (ТЗ п. 9.8.2.1). '
                        .'(2) Остальные уровни: при открытии уровень допускается в выбор приза только если накопленный фонд кейса не ниже двух таких сумм; гарантированный от этого не зависит (ТЗ п. 9.8.3). Для дорогих уровней ориентируйтесь на типичную стоимость призов в уровне. Подробнее: documentation/architecture/case-economy-levels.md.',
                    ),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('level')
            ->description(
                'Создайте уровни, затем добавьте скины в каждый уровень. Важно: у строки с наибольшим номером уровня пороговая цена при активном кейсе должна совпадать с половиной цены открытия кейса; для остальных строк та же величина участвует в правиле фонда «не ниже 2×» при открытии. См. подсказки в форме и documentation/architecture/case-economy-levels.md.',
            )
            ->columns([
                TextColumn::make('level')
                    ->label('Ур.')
                    ->numeric()
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('name')
                    ->label('Название уровня')
                    ->weight('bold'),

                TextColumn::make('chance')
                    ->label('Шанс, %')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 4).'%'),

                TextColumn::make('prize_amount')
                    ->label('Порог фонда, ₽')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Призов')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'success'),
            ])
            ->emptyStateHeading('Уровней ещё нет')
            ->emptyStateDescription('Добавьте хотя бы один уровень (обычно 3–5). После этого перейдите в каждый и добавьте скины.')
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить уровень')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                Action::make('add_skins')
                    ->label('Добавить скины')
                    ->icon('heroicon-o-squares-plus')
                    ->color('primary')
                    ->url(static fn ($record): string => CaseLevelResource::getUrl('add-skins', ['record' => $record])),
                EditAction::make()
                    ->label('Настройки уровня')
                    ->icon('heroicon-o-pencil-square')
                    ->url(static fn ($record): string => CaseLevelResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(false),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
