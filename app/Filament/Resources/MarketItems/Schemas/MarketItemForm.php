<?php

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
                    ->relationship('seller', 'id')
                    ->required(),
                TextInput::make('asset_id')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                FileUpload::make('image_url')
                    ->image(),
                Select::make('wear')
                    ->options(ItemWear::class)
                    ->required(),
                TextInput::make('float_value')
                    ->numeric(),
                Select::make('rarity')
                    ->options(ItemRarity::class)
                    ->required(),
                Select::make('category')
                    ->options(ItemCategory::class)
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Select::make('status')
                    ->options(MarketItemStatus::class)
                    ->default('active')
                    ->required(),
            ]);
    }
}
