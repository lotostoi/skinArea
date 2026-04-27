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
                    ->helperText('Уровень с наибольшим номером считается гарантированным. Для активного кейса его пороговая цена должна быть ровно 50% от цены открытия.'),

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
                    ->helperText('Защита фонда: уровень доступен только если в фонде накоплено >= 2× этой суммы. Для самого дорогого уровня ставьте среднюю цену скинов в нём. Гарантированный (наибольший номер) — всегда доступен.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('level')
            ->description('Создайте уровни, затем нажмите «Перейти к призам» чтобы добавить скины в каждый уровень.')
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
