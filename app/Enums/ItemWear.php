<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ItemWear: string implements HasLabel
{
    case FN = 'FN';
    case MW = 'MW';
    case FT = 'FT';
    case WW = 'WW';
    case BS = 'BS';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FN => 'Прямо с завода (FN)',
            self::MW => 'Немного поношенное (MW)',
            self::FT => 'После полевых испытаний (FT)',
            self::WW => 'Поношенное (WW)',
            self::BS => 'Закалённое в боях (BS)',
        };
    }
}
