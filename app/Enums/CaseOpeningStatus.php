<?php

declare(strict_types=1);

namespace App\Enums;

enum CaseOpeningStatus: string
{
    case InInventory = 'in_inventory';
    case Sold = 'sold';
    case Withdrawn = 'withdrawn';
    case UsedInUpgrade = 'used_in_upgrade';
}
