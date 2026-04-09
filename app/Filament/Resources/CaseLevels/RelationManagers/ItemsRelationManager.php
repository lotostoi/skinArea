<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaseLevels\RelationManagers;

use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Предметы уровня';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Название')->required(),
                TextInput::make('image_url')->label('URL изображения')->url()->maxLength(1024),
                TextInput::make('price')->label('Цена')->required()->numeric()->minValue(0),
                Select::make('wear')->label('Износ')->options(ItemWear::class)->required(),
                Select::make('rarity')->label('Редкость')->options(ItemRarity::class)->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable(),
                ImageColumn::make('image_url')->circular(),
                TextColumn::make('price')->numeric()->sortable(),
                TextColumn::make('wear')->badge(),
                TextColumn::make('rarity')->badge(),
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
