<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaseLevels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CaseLevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('case_id')
                    ->label('ID кейса')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                TextInput::make('level')
                    ->label('Номер уровня')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                TextInput::make('name')
                    ->label('Название уровня')
                    ->required()
                    ->maxLength(255),
                TextInput::make('chance')
                    ->label('Шанс, %')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step('0.0001')
                    ->suffix('%'),
                TextInput::make('prize_amount')
                    ->label('Пороговая цена уровня, ₽')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('₽'),
            ]);
    }
}
