<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ItemRarity: string implements HasLabel
{
    case ConsumerGrade = 'consumer_grade';
    case IndustrialGrade = 'industrial_grade';
    case MilSpec = 'mil_spec';
    case Restricted = 'restricted';
    case Classified = 'classified';
    case Covert = 'covert';
    case Contraband = 'contraband';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ConsumerGrade => 'Ширпотреб',
            self::IndustrialGrade => 'Промышленное',
            self::MilSpec => 'Армейское',
            self::Restricted => 'Запрещённое',
            self::Classified => 'Засекречённое',
            self::Covert => 'Тайное',
            self::Contraband => 'Контрабанда',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ConsumerGrade => '#b0c3d9',
            self::IndustrialGrade => '#5e98d9',
            self::MilSpec => '#4b69ff',
            self::Restricted => '#8847ff',
            self::Classified => '#d32ce6',
            self::Covert => '#eb4b4b',
            self::Contraband => '#e4ae39',
        };
    }
}
