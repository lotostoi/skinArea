<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CaseOpening;
use App\Models\User;

class CaseOpeningPolicy
{
    public function manage(User $user, CaseOpening $caseOpening): bool
    {
        return $caseOpening->user_id === $user->id;
    }
}
