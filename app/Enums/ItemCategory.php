<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ItemCategory: string implements HasLabel
{
    case Knives = 'knives';
    case Gloves = 'gloves';
    case Pistols = 'pistols';
    case Rifles = 'rifles';
    case SMGs = 'smgs';
    case Heavy = 'heavy';
    case Other = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Knives => 'Ножи',
            self::Gloves => 'Перчатки',
            self::Pistols => 'Пистолеты',
            self::Rifles => 'Винтовки',
            self::SMGs => 'ПП',
            self::Heavy => 'Тяжёлое',
            self::Other => 'Прочее',
        };
    }
}
