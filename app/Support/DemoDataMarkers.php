<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Маркеры демо-данных витрины (см. DemoSeeder, команда demo:wipe).
 */
final class DemoDataMarkers
{
    public const string STEAM_ID_PREFIX = 'demo_';

    public const string USERNAME_PREFIX = 'demo_seller_';

    public const string CASE_CATEGORY_NAME = 'Демо-кейсы';

    /** Категория кейсов из JSON-импорта (`CasesImportService`, `cases:import`). */
    public const string IMPORTED_CASE_CATEGORY_NAME = 'Официальные кейсы CS2';

    public const string LISTING_ASSET_PREFIX = 'demo_asset_';

    public const string SOLD_ASSET_PREFIX = 'demo_sold_';

    public static function whereQueryIsDemoSeller(Builder $query): void
    {
        $query->where('steam_id', 'like', self::STEAM_ID_PREFIX.'%')
            ->where('username', 'like', self::USERNAME_PREFIX.'%');
    }

    public static function isDemoSellerUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $steamId = (string) $user->steam_id;
        $username = (string) $user->username;

        return str_starts_with($steamId, self::STEAM_ID_PREFIX)
            && str_starts_with($username, self::USERNAME_PREFIX);
    }

    public static function isDemoAssetId(?string $assetId): bool
    {
        if ($assetId === null || $assetId === '') {
            return false;
        }

        return str_starts_with($assetId, self::LISTING_ASSET_PREFIX)
            || str_starts_with($assetId, self::SOLD_ASSET_PREFIX);
    }
}
