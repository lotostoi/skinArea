<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketItems\Tables;

use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MarketItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('seller.username')
                    ->label('Продавец')
                    ->searchable(query: self::sellerUsernameSearch())
                    ->sortable(),
                TextColumn::make('seller_id')
                    ->label('ID продавца')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('asset_id')
                    ->label('Asset ID (Steam)')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                ImageColumn::make('image_url')
                    ->label('Изображение'),
                TextColumn::make('wear')
                    ->label('Износ')
                    ->badge()
                    ->searchable(),
                TextColumn::make('float_value')
                    ->label('Float')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rarity')
                    ->label('Редкость')
                    ->badge()
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Категория')
                    ->badge()
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB', locale: 'ru_RU')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return Closure(Builder, string): Builder
     */
    private static function sellerUsernameSearch(): Closure
    {
        return static function (Builder $query, string $search): Builder {
            return $query->whereHas(
                'seller',
                static fn (Builder $sellerQuery): Builder => $sellerQuery->where(
                    'username',
                    'ilike',
                    '%'.$search.'%',
                ),
            );
        };
    }
}
