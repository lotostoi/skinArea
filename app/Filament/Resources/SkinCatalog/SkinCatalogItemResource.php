<?php

declare(strict_types=1);

namespace App\Filament\Resources\SkinCatalog;

use App\Enums\ItemCategory;
use App\Enums\ItemRarity;
use App\Filament\Resources\SkinCatalog\Pages\ListSkinCatalogItems;
use App\Jobs\SyncSkinCatalogJob;
use App\Models\SkinCatalogItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class SkinCatalogItemResource extends Resource
{
    protected static ?string $model = SkinCatalogItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Кейсы';

    protected static ?string $modelLabel = 'скин каталога';

    protected static ?string $pluralModelLabel = 'Каталог скинов CS2';

    protected static ?string $navigationLabel = 'Каталог скинов';

    protected static ?int $navigationSort = 99;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Split::make([
                    ImageColumn::make('image_url')
                        ->label('')
                        ->getStateUsing(static fn (SkinCatalogItem $record): ?string => $record->image_url)
                        ->square()
                        ->size(80)
                        ->grow(false)
                        ->extraImgAttributes([
                            'loading' => 'lazy',
                            'alt' => 'Skin image',
                            'style' => 'object-fit:contain;background:#1a1a24;border-radius:8px;padding:6px',
                        ]),

                    Stack::make([
                        TextColumn::make('name')
                            ->label('Название')
                            ->weight('bold')
                            ->size('sm')
                            ->searchable()
                            ->sortable(),

                        TextColumn::make('weapon_name')
                            ->label('Оружие')
                            ->searchable()
                            ->sortable()
                            ->placeholder('—')
                            ->color('gray')
                            ->size('xs'),

                        Split::make([
                            TextColumn::make('rarity')
                                ->label('Редкость')
                                ->badge()
                                ->formatStateUsing(static function (?string $state): string {
                                    if ($state === null) {
                                        return '—';
                                    }
                                    $enum = ItemRarity::tryFrom($state);

                                    return $enum?->getLabel() ?? $state;
                                })
                                ->color(static function (?string $state): string {
                                    return match (ItemRarity::tryFrom((string) $state)) {
                                        ItemRarity::ConsumerGrade => 'gray',
                                        ItemRarity::IndustrialGrade => 'info',
                                        ItemRarity::MilSpec => 'primary',
                                        ItemRarity::Restricted => 'purple',
                                        ItemRarity::Classified => 'fuchsia',
                                        ItemRarity::Covert => 'danger',
                                        ItemRarity::Contraband => 'warning',
                                        default => 'gray',
                                    };
                                })
                                ->sortable(),

                            TextColumn::make('category')
                                ->label('Категория')
                                ->badge()
                                ->formatStateUsing(static function (?string $state): string {
                                    if ($state === null) {
                                        return '—';
                                    }
                                    $enum = ItemCategory::tryFrom($state);

                                    return $enum?->getLabel() ?? $state;
                                })
                                ->color('gray'),
                        ]),

                        TextColumn::make('market_price')
                            ->label('Цена, USD')
                            ->numeric(decimalPlaces: 2)
                            ->prefix('$')
                            ->sortable()
                            ->placeholder('—')
                            ->weight('bold')
                            ->color('primary'),

                        Split::make([
                            TextColumn::make('price_synced_at')
                                ->label('Цена обновлена')
                                ->dateTime('d.m.Y H:i')
                                ->sortable()
                                ->placeholder('—')
                                ->size('xs')
                                ->color('gray'),

                            TextColumn::make('last_synced_at')
                                ->label('Синхронизация')
                                ->dateTime('d.m.Y H:i')
                                ->sortable()
                                ->placeholder('—')
                                ->size('xs')
                                ->color('gray'),
                        ]),
                    ])->space(1),
                ]),
            ])
            ->filters([
                SelectFilter::make('rarity')
                    ->label('Редкость')
                    ->options(ItemRarity::class),

                SelectFilter::make('category')
                    ->label('Категория')
                    ->options(ItemCategory::class),
            ])
            ->headerActions([
                Action::make('sync_catalog')
                    ->label('Синхронизировать каталог')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->action(static function (): void {
                        SyncSkinCatalogJob::dispatch();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Синхронизировать каталог скинов')
                    ->modalDescription('Запустит загрузку ~10 000 скинов CS2 из ByMykel/CSGO-API в фоновом режиме. Это займёт 30–90 секунд. Прогресс отображается в логах.')
                    ->modalSubmitActionLabel('Запустить'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSkinCatalogItems::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
