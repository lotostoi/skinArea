<?php

declare(strict_types=1);

namespace App\Enums;

enum SupportTicketStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
