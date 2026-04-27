<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\Tables;

use App\Models\GameCase;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
            ->reorderable('sort_order')
            ->paginatedWhileReordering(false)
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Split::make([
                    ImageColumn::make('image_url')
                        ->label('')
                        ->getStateUsing(static fn (GameCase $record): ?string => self::resolveImageUrl($record->image_url))
                        ->square()
                        ->size(80)
                        ->grow(false)
                        ->extraImgAttributes(static fn (GameCase $record): array => [
                            'loading' => 'lazy',
                            'alt' => $record->name,
                            'style' => 'object-fit:contain;background:#1a1a24;border-radius:8px;padding:6px',
                        ]),

                    Stack::make([
                        TextColumn::make('name')
                            ->label('Название')
                            ->weight('bold')
                            ->size('sm')
                            ->searchable(),

                        Split::make([
                            TextColumn::make('category.name')
                                ->label('Категория')
                                ->badge()
                                ->color('gray')
                                ->searchable(),

                            TextColumn::make('sort_order')
                                ->label('Порядок')
                                ->prefix('#')
                                ->color('gray')
                                ->size('xs'),
                        ]),

                        TextColumn::make('price')
                            ->label('Цена')
                            ->money('RUB', locale: 'ru_RU')
                            ->weight('bold')
                            ->color('primary'),

                        Split::make([
                            IconColumn::make('is_active')
                                ->label('Активен в каталоге')
                                ->boolean()
                                ->grow(false)
                                ->tooltip('Статус каталога. Включение/выключение выполняется только на странице редактирования, где есть валидация и сообщения об ошибках.'),

                            IconColumn::make('is_featured_on_home')
                                ->label('На главной (витрина)')
                                ->boolean()
                                ->grow(false)
                                ->tooltip('Только витрина на главной. Этот флаг сам по себе не активирует кейс.'),
                        ]),
                    ])->space(1),
                ]),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Активен в каталоге')
                    ->placeholder('Все')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),

                TernaryFilter::make('is_featured_on_home')
                    ->label('На главной')
                    ->placeholder('Все')
                    ->trueLabel('Только на главной')
                    ->falseLabel('Без витрины'),

                Filter::make('price_range')
                    ->label('Диапазон цены')
                    ->form([
                        TextInput::make('price_from')
                            ->label('Цена от, ₽')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('price_to')
                            ->label('Цена до, ₽')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(static function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['price_from'] ?? null),
                                static fn (Builder $builder): Builder => $builder->where('price', '>=', (float) $data['price_from']),
                            )
                            ->when(
                                filled($data['price_to'] ?? null),
                                static fn (Builder $builder): Builder => $builder->where('price', '<=', (float) $data['price_to']),
                            );
                    }),

                Filter::make('created_range')
                    ->label('Дата создания')
                    ->form([
                        DatePicker::make('created_from')->label('Создан от'),
                        DatePicker::make('created_to')->label('Создан до'),
                    ])
                    ->query(static function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['created_from'] ?? null),
                                static fn (Builder $builder): Builder => $builder->whereDate('created_at', '>=', $data['created_from']),
                            )
                            ->when(
                                filled($data['created_to'] ?? null),
                                static fn (Builder $builder): Builder => $builder->whereDate('created_at', '<=', $data['created_to']),
                            );
                    }),

                Filter::make('updated_range')
                    ->label('Дата обновления')
                    ->form([
                        DatePicker::make('updated_from')->label('Обновлен от'),
                        DatePicker::make('updated_to')->label('Обновлен до'),
                    ])
                    ->query(static function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['updated_from'] ?? null),
                                static fn (Builder $builder): Builder => $builder->whereDate('updated_at', '>=', $data['updated_from']),
                            )
                            ->when(
                                filled($data['updated_to'] ?? null),
                                static fn (Builder $builder): Builder => $builder->whereDate('updated_at', '<=', $data['updated_to']),
                            );
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make()
                    ->label('Редактировать'),
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
