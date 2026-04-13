<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketItems\Schemas;

use App\Enums\ItemCategory;
use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Enums\MarketItemStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MarketItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('seller_id')
                    ->label('Продавец')
                    ->relationship('seller', 'username')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('asset_id')
                    ->label('Asset ID (Steam)')
                    ->required(),
                TextInput::make('name')
                    ->label('Название')
                    ->required(),
                FileUpload::make('image_url')
                    ->label('Изображение')
                    ->image(),
                Select::make('wear')
                    ->label('Износ')
                    ->options(ItemWear::class)
                    ->required(),
                TextInput::make('float_value')
                    ->label('Float')
                    ->numeric(),
                Select::make('rarity')
                    ->label('Редкость')
                    ->options(ItemRarity::class)
                    ->required(),
                Select::make('category')
                    ->label('Категория')
                    ->options(ItemCategory::class)
                    ->required(),
                TextInput::make('price')
                    ->label('Цена, ₽')
                    ->required()
                    ->numeric()
                    ->suffix('₽'),
                Select::make('status')
                    ->label('Статус')
                    ->options(MarketItemStatus::class)
                    ->default('active')
                    ->required(),
            ]);
    }
}
