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
                    ->maxValue(5)
                    ->helperText(
                        'Строка с наибольшим номером — гарантированный уровень. Его пороговая цена при активном кейсе должна быть ровно 50% от цены открытия кейса (см. поле ниже и documentation/architecture/case-economy-levels.md).',
                    ),
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
                    ->prefix('₽')
                    ->helperText(
                        'Гарантированный уровень (макс. номер): ровно 50% цены открытия кейса при активном кейсе. Остальные: при открытии уровень в розыгрыше только если фонд ≥ 2× этой суммы. Подробнее: documentation/architecture/case-economy-levels.md.',
                    ),
            ]);
    }
}
