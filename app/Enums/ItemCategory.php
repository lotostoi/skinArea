<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemCategory: string
{
    case Knives = 'knives';
    case Gloves = 'gloves';
    case Pistols = 'pistols';
    case Rifles = 'rifles';
    case SMGs = 'smgs';
    case Heavy = 'heavy';
    case Other = 'other';
}
