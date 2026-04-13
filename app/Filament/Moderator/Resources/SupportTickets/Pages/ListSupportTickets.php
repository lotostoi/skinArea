<?php

declare(strict_types=1);

namespace App\Filament\Moderator\Resources\SupportTickets\Pages;

use App\Filament\Moderator\Resources\SupportTickets\SupportTicketResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;
}
