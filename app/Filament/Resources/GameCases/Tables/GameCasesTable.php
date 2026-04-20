<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\Tables;

use App\Models\GameCase;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class GameCasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Обложка')
                    ->getStateUsing(fn (GameCase $record): ?string => self::resolveImageUrl($record->image_url))
                    ->square()
                    ->imageSize(56)
                    ->extraImgAttributes(fn (GameCase $record): array => [
                        'loading' => 'lazy',
                        'alt' => $record->name,
                    ]),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Цена открытия')
                    ->money('RUB', locale: 'ru_RU')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Категория')
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                ToggleColumn::make('is_featured_on_home')
                    ->label('На главной')
                    ->tooltip('Показывать в блоке популярных кейсов на главной (на сайте попадут только активные кейсы)'),
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
                EditAction::make()
                    ->label('Редактировать'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }

    private static function resolveImageUrl(?string $imageUrl): ?string
    {
        if (blank($imageUrl)) {
            return null;
        }

        if (filter_var($imageUrl, FILTER_VALIDATE_URL) !== false || Str::startsWith($imageUrl, 'data:')) {
            return $imageUrl;
        }

        if (Str::startsWith($imageUrl, '/')) {
            return URL::to($imageUrl);
        }

        $disk = Storage::disk('public');

        if (! $disk instanceof FilesystemAdapter) {
            return null;
        }

        return $disk->url($imageUrl);
    }
}
