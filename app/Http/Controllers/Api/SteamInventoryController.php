<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\SteamInventoryFetchException;
use App\Http\Controllers\Controller;
use App\Http\Resources\SteamInventoryAssetResource;
use App\Services\SteamInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SteamInventoryController extends Controller
{
    public function __construct(
        private readonly SteamInventoryService $steamInventory,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        $tradeUrl = $user->trade_url !== null ? trim((string) $user->trade_url) : '';
        if ($tradeUrl === '') {
            return response()->json([
                'message' => 'Укажите trade-ссылку Steam в разделе настроек маркета, чтобы просматривать инвентарь.',
                'errors' => (object) [],
            ], 422);
        }

        try {
            $result = $this->steamInventory->fetchPageForSteamId($user->steam_id);
            $items = $result->items;
        } catch (SteamInventoryFetchException $e) {
            if (config('app.debug')) {
                Log::info('inventory.steam_failed', [
                    'user_id' => $user->id,
                    'steam_id' => $user->steam_id,
                    'message' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'message' => $e->getMessage(),
                'errors' => (object) [],
            ], 422);
        }

        if (config('app.debug')) {
            Log::info('inventory.steam_ok', [
                'user_id' => $user->id,
                'steam_id' => $user->steam_id,
                'steam_raw_assets' => $result->rawAssetCount,
                'mapped_items' => count($items),
                'steam_app_id' => (int) config('skinsarena.steam_inventory.app_id'),
                'only_tradable' => (bool) config('skinsarena.steam_inventory.only_tradable'),
            ]);
        }

        return SteamInventoryAssetResource::collection($items)
            ->additional([
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => count($items),
                    'steam_user_id' => (string) $user->steam_id,
                    'steam_app_id' => (int) config('skinsarena.steam_inventory.app_id'),
                    'steam_context_id' => (int) config('skinsarena.steam_inventory.context_id'),
                    'inventory_game' => (string) config('skinsarena.steam_inventory.game_label'),
                    'only_tradable' => (bool) config('skinsarena.steam_inventory.only_tradable'),
                    'steam_reported_total' => $result->steamTotalInventoryCount,
                    'steam_raw_assets' => $result->rawAssetCount,
                    'mapped_items' => count($items),
                ],
            ])
            ->response();
    }
}
