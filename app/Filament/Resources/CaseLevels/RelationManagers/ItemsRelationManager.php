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

    protected static ?string $title = 'Призы уровня';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название приза')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Например: «AK-47 | Redline». Отображается в деталях кейса и в инвентаре игрока.'),

                TextInput::make('image_url')
                    ->label('URL изображения')
                    ->url()
                    ->maxLength(1024)
                    ->helperText('Пока в MVP нет собственного CDN: можно указать путь к файлу в public/ или внешний HTTPS-URL с превью скина.'),

                TextInput::make('price')
                    ->label('Цена, ₽')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('₽')
                    ->helperText('Цена приза в балансе. В MVP задаётся вручную — автоценообразование по внешним маркетам появится позже.'),

                Select::make('wear')
                    ->label('Износ')
                    ->options(ItemWear::class)
                    ->required(),

                Select::make('rarity')
                    ->label('Редкость')
                    ->options(ItemRarity::class)
                    ->required()
                    ->helperText('Редкость определяет цвет рамки карточки в UI.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('image_url')->label('')->circular(),
                TextColumn::make('name')->label('Название')->searchable(),
                TextColumn::make('price')->label('Цена, ₽')->numeric()->sortable(),
                TextColumn::make('wear')->label('Износ')->badge(),
                TextColumn::make('rarity')->label('Редкость')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()->label('Добавить приз'),
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
