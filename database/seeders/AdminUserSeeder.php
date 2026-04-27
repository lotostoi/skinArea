<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BalanceType;
use App\Enums\UserRole;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('skinsarena.admin.email');
        $password = (string) config('skinsarena.admin.password');
        $steamId = (string) config('skinsarena.admin.steam_id');

        $user = User::query()->firstOrNew(['steam_id' => $steamId]);
        $user->forceFill([
            'email' => $email,
            'email_verified_at' => now(),
            'username' => 'admin',
            'avatar_url' => null,
            'trade_url' => null,
            'password' => $password,
            'role' => UserRole::Admin,
            'is_banned' => false,
            'banned_until' => null,
            'ban_reason' => null,
        ])->save();

        foreach ([BalanceType::Main, BalanceType::Hold] as $type) {
            Balance::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $type,
                ],
                ['amount' => 0],
            );
        }
    }
}
