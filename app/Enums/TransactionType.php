<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Purchase = 'purchase';
    case Sale = 'sale';
    case CaseOpen = 'case_open';
    case CaseSell = 'case_sell';
    case Upgrade = 'upgrade';
}
