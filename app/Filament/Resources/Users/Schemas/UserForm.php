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
                    ->label('Steam ID')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('username')
                    ->label('Никнейм (Steam)')
                    ->required(),
                TextInput::make('avatar_url')
                    ->label('URL аватара')
                    ->url(),
                TextInput::make('trade_url')
                    ->label('Trade-ссылка')
                    ->url(),
                TextInput::make('email')
                    ->label('Электронная почта')
                    ->email()
                    ->helperText('Нужна для входа в админ-панель (admin) и панель модератора (/moderator).'),
                DateTimePicker::make('email_verified_at')
                    ->label('Дата подтверждения почты')
                    ->seconds(false),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Оставьте пустым, чтобы не менять пароль.'),
                Select::make('role')
                    ->label('Роль')
                    ->options(UserRole::class)
                    ->required(),
                Toggle::make('is_banned')
                    ->label('Заблокирован')
                    ->required(),
                DateTimePicker::make('banned_until')
                    ->label('Бан до')
                    ->seconds(false),
                Textarea::make('ban_reason')
                    ->label('Причина бана')
                    ->columnSpanFull(),
                DateTimePicker::make('support_muted_until')
                    ->label('Ограничение чата поддержки до')
                    ->seconds(false)
                    ->helperText('Пока дата в будущем, пользователь не может писать в тикеты (API).'),
            ]);
    }
}
