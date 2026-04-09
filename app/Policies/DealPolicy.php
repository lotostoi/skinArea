<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    public function send(User $user, Deal $deal): bool
    {
        return $deal->seller_id === $user->id;
    }

    public function cancel(User $user, Deal $deal): bool
    {
        return $deal->buyer_id === $user->id || $deal->seller_id === $user->id;
    }
}
