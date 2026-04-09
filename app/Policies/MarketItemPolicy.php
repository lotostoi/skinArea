<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MarketItem;
use App\Models\User;

class MarketItemPolicy
{
    public function delete(User $user, MarketItem $marketItem): bool
    {
        return $marketItem->seller_id === $user->id;
    }
}
