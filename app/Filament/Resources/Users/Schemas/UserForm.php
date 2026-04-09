<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('steam_id')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('username')
                    ->required(),
                TextInput::make('avatar_url')
                    ->url(),
                TextInput::make('trade_url')
                    ->url(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->helperText('Нужен для входа в админ-панель (роль admin).'),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Оставьте пустым, чтобы не менять пароль.'),
                Select::make('role')
                    ->options(UserRole::class)
                    ->required(),
                Toggle::make('is_banned')
                    ->required(),
                DateTimePicker::make('banned_until'),
                Textarea::make('ban_reason')
                    ->columnSpanFull(),
            ]);
    }
}
