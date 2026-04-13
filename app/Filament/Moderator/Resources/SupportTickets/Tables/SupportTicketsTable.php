<?php

declare(strict_types=1);

namespace App\Filament\Moderator\Resources\SupportTickets\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.username')->label('Пользователь')->searchable(),
                TextColumn::make('subject')->label('Тема')->limit(40)->searchable(),
                TextColumn::make('status')->label('Статус')->badge()->sortable(),
                TextColumn::make('updated_at')->label('Обновлён')->dateTime()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
