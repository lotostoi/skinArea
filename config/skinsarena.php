<?php

declare(strict_types=1);

return [

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@skinsarena.local'),
        'password' => env('ADMIN_PASSWORD', 'change-me-in-production'),
        'steam_id' => env('ADMIN_STEAM_ID', '76561198000000001'),
    ],

    'marketplace' => [
        'commission' => (float) env('SKINSARENA_MARKET_COMMISSION_PERCENT', 5.0),
        'trade_timeout_minutes' => (int) env('SKINSARENA_TRADE_TIMEOUT_MINUTES', 15),
    ],

    'cases' => [
        'open_animation_seconds' => (int) env('SKINSARENA_CASE_ANIMATION_SECONDS', 5),
    ],

    'upgrade' => [
        'min_bet' => (float) env('SKINSARENA_UPGRADE_MIN_BET', 0.01),
        'max_bet' => (float) env('SKINSARENA_UPGRADE_MAX_BET', 100_000.0),
    ],

    'balance' => [
        'min_deposit' => (float) env('SKINSARENA_MIN_DEPOSIT', 1.0),
        'min_withdrawal' => (float) env('SKINSARENA_MIN_WITHDRAWAL', 10.0),
    ],

    'skin_catalog' => [
        'source_url' => env(
            'SKIN_CATALOG_SOURCE_URL',
            'https://raw.githubusercontent.com/ByMykel/CSGO-API/main/public/api/en/skins.json',
        ),
        'http_timeout_seconds' => (int) env('SKIN_CATALOG_HTTP_TIMEOUT', 120),
        'user_agent' => env('SKIN_CATALOG_USER_AGENT', 'SkinsArena/1.0'),
    ],

    'steam_inventory' => [
        // App ID Steam: 730 = CS2, 570 = Dota 2 (только для временного теста инвентаря; прод — 730).
        'app_id' => (int) env('SKINSARENA_STEAM_INVENTORY_APP_ID', 730),
        'context_id' => (int) env('SKINSARENA_STEAM_INVENTORY_CONTEXT_ID', 2),
        'game_label' => env('SKINSARENA_STEAM_INVENTORY_GAME_LABEL', 'Counter-Strike 2'),
        'http_timeout_seconds' => (int) env('SKINSARENA_STEAM_INVENTORY_TIMEOUT', 25),
        // Браузерный UA: голый «SkinsArena» часто даёт 403 на /inventory/; json-URL терпимее, но UA оставляем «как у Chrome».
        'user_agent' => env(
            'SKINSARENA_STEAM_INVENTORY_USER_AGENT',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        ),
        'per_request_count' => (int) env('SKINSARENA_STEAM_INVENTORY_COUNT', 2000),
        'max_pages' => (int) env('SKINSARENA_STEAM_INVENTORY_MAX_PAGES', 10),
        'economy_image_base_url' => env(
            'SKINSARENA_STEAM_ECONOMY_IMAGE_BASE',
            'https://community.cloudflare.steamstatic.com/economy/image/',
        ),
        // false — показывать в ЛК и неторгуемые (удобно для теста Dota 2); на маркет — только tradable.
        'only_tradable' => filter_var(
            env('SKINSARENA_STEAM_INVENTORY_ONLY_TRADABLE', 'true'),
            FILTER_VALIDATE_BOOLEAN,
        ),
        // При 403 со всех URL Steam (датацентр, Docker): http://user:pass@host:port или socks5://127.0.0.1:1080
        'http_proxy' => env('SKINSARENA_STEAM_INVENTORY_HTTP_PROXY'),
    ],

    'steam_web_api' => [
        'key' => env('STEAM_API_KEY', ''),
        'http_timeout_seconds' => (int) env('SKINSARENA_STEAM_WEB_API_TIMEOUT', 10),
        'trade_ban_check_enabled' => filter_var(
            env('SKINSARENA_STEAM_TRADE_BAN_CHECK', true),
            FILTER_VALIDATE_BOOLEAN,
        ) && (string) env('STEAM_API_KEY', '') !== '',
    ],

];
