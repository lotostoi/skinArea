<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\RelationManagers;

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

    protected static ?string $title = 'Уровни и шансы';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('level')
                    ->label('Уровень (1–5)')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->helperText('1 — самый частый уровень, 5 — самый редкий. Уровни с одинаковым номером не допускаются.'),

                TextInput::make('name')
                    ->label('Название уровня')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Пример: «Армейское», «Тайное», «Нож/Перчатки». Видно пользователю в деталях кейса.'),

                TextInput::make('chance')
                    ->label('Шанс, %')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step('0.0001')
                    ->helperText('Вероятность выпадения уровня в процентах. Сумма шансов всех уровней должна быть 100. Проверяется вручную.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('level')
            ->columns([
                TextColumn::make('level')->label('Уровень')->numeric()->sortable(),
                TextColumn::make('name')->label('Название')->searchable(),
                TextColumn::make('chance')->label('Шанс, %')->numeric()->sortable(),
                TextColumn::make('items_count')->counts('items')->label('Призов')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()->label('Добавить уровень'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
