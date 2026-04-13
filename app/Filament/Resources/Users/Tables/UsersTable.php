<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('steam_id')
                    ->label('Steam ID')
                    ->searchable(),
                TextColumn::make('username')
                    ->label('Никнейм')
                    ->searchable(),
                TextColumn::make('avatar_url')
                    ->label('URL аватара')
                    ->searchable(),
                TextColumn::make('trade_url')
                    ->label('Trade-ссылка')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Электронная почта')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->label('Почта подтверждена')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->searchable(),
                IconColumn::make('is_banned')
                    ->label('Заблокирован')
                    ->boolean(),
                TextColumn::make('banned_until')
                    ->label('Бан до')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Удалён')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
