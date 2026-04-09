<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemRarity: string
{
    case ConsumerGrade = 'consumer_grade';
    case IndustrialGrade = 'industrial_grade';
    case MilSpec = 'mil_spec';
    case Restricted = 'restricted';
    case Classified = 'classified';
    case Covert = 'covert';
    case Contraband = 'contraband';
}
