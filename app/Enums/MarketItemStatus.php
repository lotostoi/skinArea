<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MarketItemStatus: string implements HasLabel
{
    case Active = 'active';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Активен',
            self::Reserved => 'Зарезервирован',
            self::Sold => 'Продан',
            self::Cancelled => 'Отменён',
        };
    }
}
