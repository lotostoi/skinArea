<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case User = 'user';
    case Moderator = 'moderator';
    case Admin = 'admin';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::User => 'Пользователь',
            self::Moderator => 'Модератор',
            self::Admin => 'Администратор',
        };
    }
}
