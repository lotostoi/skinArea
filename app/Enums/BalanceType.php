<?php

declare(strict_types=1);

namespace App\Enums;

enum BalanceType: string
{
    case Main = 'main';
    case Bonus = 'bonus';
    case Hold = 'hold';
}
