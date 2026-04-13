<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SteamPlayerBansService
{
    public function isEconomyTradeBanned(string $steamId64): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $key = (string) config('skinsarena.steam_web_api.key');
        $timeout = (int) config('skinsarena.steam_web_api.http_timeout_seconds', 10);
        $url = 'https://api.steampowered.com/ISteamUser/GetPlayerBans/v1/';

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($url, [
                    'key' => $key,
                    'steamids' => $steamId64,
                ]);
        } catch (\Throwable $e) {
            Log::warning('steam.get_player_bans_failed', [
                'steam_id' => $steamId64,
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('steam.get_player_bans_http', [
                'steam_id' => $steamId64,
                'status' => $response->status(),
            ]);

            return false;
        }

        $players = $response->json('players');
        if (! is_array($players) || $players === []) {
            return false;
        }

        $first = $players[0];
        if (! is_array($first)) {
            return false;
        }

        $economy = isset($first['EconomyBan']) ? strtolower((string) $first['EconomyBan']) : 'none';

        return $economy !== 'none';
    }

    public function isEnabled(): bool
    {
        return (bool) config('skinsarena.steam_web_api.trade_ban_check_enabled');
    }
}
