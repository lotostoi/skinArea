<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameCases\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GameCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основное')
                    ->description('Название, цена и категория — обязательные поля.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Название кейса')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Chroma Case')
                            ->columnSpanFull(),

                        TextInput::make('price')
                            ->label('Цена открытия, ₽')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('₽')
                            ->helperText('Списывается с баланса пользователя при нажатии «Открыть».'),

                        Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Категории заводятся в разделе «Категории кейсов».'),

                        TextInput::make('sort_order')
                            ->label('Порядок в списке')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Меньше = выше. Кейсы с одинаковым числом идут по ID.'),

                        Textarea::make('description')
                            ->label('Описание')
                            ->nullable()
                            ->rows(2)
                            ->maxLength(1000)
                            ->placeholder('Коллекция армейских скинов для пистолетов и винтовок...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Обложка и оформление')
                    ->description('Обложка отображается на карточке и странице кейса.')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('image_url')
                            ->label('Обложка кейса')
                            ->image()
                            ->disk('public')
                            ->directory('cases')
                            ->imagePreviewHeight('160')
                            ->helperText('Рекомендуемый размер: 512×512 px. PNG/WebP с прозрачным фоном.'),

                        ColorPicker::make('shadow_color')
                            ->label('Цвет тени карточки')
                            ->nullable()
                            ->helperText(
                                'Свечение вокруг обложки на сайте (каталог, главная, страница кейса). Подберите под доминирующий цвет PNG. Примеры: #eb4b4b для тайных, #8847ff для запрещённых.',
                            ),
                    ]),

                Section::make('Видимость')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(false)
                            ->helperText('По умолчанию создаётся как черновик. Включайте после настройки уровней и проверки экономики.'),

                        Toggle::make('is_featured_on_home')
                            ->label('Показывать на главной')
                            ->default(false)
                            ->helperText('Отмеченные активные кейсы попадают в блок «Популярные» на главной странице.'),
                    ]),
            ]);
    }
}
