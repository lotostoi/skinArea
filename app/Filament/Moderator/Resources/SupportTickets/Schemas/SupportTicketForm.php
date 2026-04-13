<?php

declare(strict_types=1);

namespace App\Filament\Moderator\Resources\SupportTickets\Schemas;

use App\Enums\SupportTicketStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user.username')
                    ->label('Пользователь')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('subject')
                    ->label('Тема')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('status')
                    ->label('Статус')
                    ->options(SupportTicketStatus::class)
                    ->required(),
            ]);
    }
}
