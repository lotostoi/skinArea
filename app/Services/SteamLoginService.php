<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BalanceType;
use App\Models\Balance;
use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SteamLoginService
{
    public function findOrUpdateUser(SocialiteUser $steamAccount): User
    {
        $steamId = $this->normalizeSteamId((string) $steamAccount->getId());

        $user = User::query()->where('steam_id', $steamId)->first();

        if ($user === null) {
            $user = User::query()->create([
                'steam_id' => $steamId,
                'username' => $steamAccount->getNickname() ?? 'steam_'.$steamId,
                'avatar_url' => $steamAccount->getAvatar(),
            ]);

            foreach ([BalanceType::Main, BalanceType::Bonus, BalanceType::Hold] as $type) {
                Balance::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'type' => $type,
                    ],
                    ['amount' => 0],
                );
            }

            return $user;
        }

        $user->update([
            'username' => $steamAccount->getNickname() ?? $user->username,
            'avatar_url' => $steamAccount->getAvatar() ?? $user->avatar_url,
        ]);

        foreach ([BalanceType::Main, BalanceType::Bonus, BalanceType::Hold] as $type) {
            Balance::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $type,
                ],
                ['amount' => 0],
            );
        }

        return $user->fresh();
    }

    private function normalizeSteamId(string $id): string
    {
        return preg_replace('/\D/', '', $id) ?? $id;
    }
}
