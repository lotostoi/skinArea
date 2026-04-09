<?php

declare(strict_types=1);

namespace App\Enums;

enum MarketItemStatus: string
{
    case Active = 'active';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Cancelled = 'cancelled';
}
