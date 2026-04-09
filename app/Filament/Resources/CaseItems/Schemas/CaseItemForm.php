<?php

namespace App\Filament\Resources\CaseItems\Schemas;

use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CaseItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('case_level_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                FileUpload::make('image_url')
                    ->image(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Select::make('wear')
                    ->options(ItemWear::class)
                    ->required(),
                Select::make('rarity')
                    ->options(ItemRarity::class)
                    ->required(),
            ]);
    }
}
