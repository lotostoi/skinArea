<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaseCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CaseCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название категории')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Например: «Операции», «Оружейные кейсы», «Ножи». Видно пользователю на вкладке «Кейсы».'),

                TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Чем меньше число, тем выше категория в списке.'),

                Toggle::make('is_visible')
                    ->label('Показывать в SPA')
                    ->default(true)
                    ->helperText('Если выключено — кейсы этой категории скрыты от пользователей, но остаются в админке.'),
            ]);
    }
}
