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
                    ->label('Уровень')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                TextInput::make('name')
                    ->label('Название уровня')
                    ->required(),
                TextInput::make('chance')
                    ->label('Шанс, %')
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('level')->label('Уровень')->numeric()->sortable(),
                TextColumn::make('name')->label('Название')->searchable(),
                TextColumn::make('chance')->label('Шанс %')->numeric()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make(),
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
