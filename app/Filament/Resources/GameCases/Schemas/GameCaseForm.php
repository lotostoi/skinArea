<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GameCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название кейса')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Пример: «Chroma Case», «Операция Феникс».'),

                FileUpload::make('image_url')
                    ->label('Обложка')
                    ->image()
                    ->directory('cases')
                    ->helperText('В MVP файл сохраняется локально без CDN-процессинга. Для демо-витрины можно оставить пустым — на фронте будет заглушка.'),

                TextInput::make('price')
                    ->label('Цена открытия, ₽')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('₽')
                    ->helperText('Стоимость списывается с основного баланса пользователя при открытии.'),

                Select::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Категории видны пользователю на вкладке «Кейсы» как фильтр. Заводятся отдельно в разделе «Категории кейсов».'),

                TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Чем меньше число, тем выше кейс в списке.'),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true)
                    ->helperText('Неактивные кейсы скрыты в SPA и недоступны для открытия.'),

                Toggle::make('is_featured_on_home')
                    ->label('Показывать на главной')
                    ->default(false)
                    ->helperText('Популярные кейсы на главной и в верхней ленте берутся из отмеченных активных кейсов. Порядок — по полю «Порядок сортировки» выше.'),
            ]);
    }
}
