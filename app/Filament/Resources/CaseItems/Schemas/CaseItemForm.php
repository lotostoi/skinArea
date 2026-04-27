<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaseItems\Schemas;

use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Models\SkinCatalogItem;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Schemas\Schema;

class CaseItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('skin_catalog_external_id')
                    ->label('Скин из каталога')
                    ->placeholder('Введите название скина...')
                    ->searchable()
                    ->getSearchResultsUsing(static function (string $search): array {
                        return SkinCatalogItem::query()
                            ->where('name', 'ilike', "%{$search}%")
                            ->orderBy('name')
                            ->limit(30)
                            ->pluck('name', 'external_id')
                            ->all();
                    })
                    ->getOptionLabelUsing(static function (?string $value): ?string {
                        if ($value === null) {
                            return null;
                        }

                        return SkinCatalogItem::where('external_id', $value)->value('name');
                    })
                    ->afterStateUpdated(static function (?string $state, Set $set): void {
                        if ($state === null) {
                            return;
                        }
                        $item = SkinCatalogItem::where('external_id', $state)->first();
                        if ($item === null) {
                            return;
                        }
                        $set('name', $item->name);
                        $set('image_url', $item->image_url ?? '');
                        if ($item->rarity !== null) {
                            $set('rarity', $item->rarity);
                        }
                        if ($item->market_price !== null) {
                            $set('price', (string) $item->market_price);
                        }
                    })
                    ->live()
                    ->helperText('Начните вводить название скина. После выбора поля ниже заполнятся автоматически.'),

                Placeholder::make('catalog_preview')
                    ->label('Превью')
                    ->content(static function ($get): string {
                        $externalId = $get('skin_catalog_external_id');
                        if (! $externalId) {
                            return 'Выберите скин из каталога для предпросмотра.';
                        }
                        $item = SkinCatalogItem::where('external_id', $externalId)->first();
                        if (! $item || ! $item->image_url) {
                            return 'Изображение недоступно.';
                        }

                        return '<img src="'.e($item->image_url).'" alt="'.e($item->name).'" style="height:80px;object-fit:contain;">';
                    })
                    ->extraAttributes(['class' => 'min-h-[80px]'])
                    ->visible(static fn ($get): bool => (bool) $get('skin_catalog_external_id')),

                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Заполняется автоматически при выборе скина из каталога.'),

                TextInput::make('image_url')
                    ->label('URL изображения')
                    ->url()
                    ->maxLength(1024)
                    ->helperText('Заполняется автоматически. Можно указать вручную.'),

                TextInput::make('price')
                    ->label('Цена, ₽')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('₽')
                    ->helperText('Предустанавливается из рыночной цены каталога (USD). Скорректируйте под курс и политику платформы.'),

                Select::make('wear')
                    ->label('Износ')
                    ->options(ItemWear::class)
                    ->required(),

                Select::make('rarity')
                    ->label('Редкость')
                    ->options(ItemRarity::class)
                    ->required()
                    ->helperText('Заполняется автоматически при выборе скина.'),
            ]);
    }
}
