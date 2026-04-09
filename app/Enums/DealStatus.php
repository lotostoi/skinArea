<?php

declare(strict_types=1);

namespace App\Enums;

enum DealStatus: string
{
    case Created = 'created';
    case Paid = 'paid';
    case TradeSent = 'trade_sent';
    case TradeAccepted = 'trade_accepted';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
