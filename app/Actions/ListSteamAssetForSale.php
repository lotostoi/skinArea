<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MarketItemStatus;
use App\Models\MarketItem;
use App\Models\User;
use App\Services\SteamInventoryService;
use App\Services\SteamPlayerBansService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListSteamAssetForSale
{
    public function __construct(
        private readonly SteamInventoryService $steamInventory,
        private readonly SteamPlayerBansService $steamPlayerBans,
    ) {}

    /**
     * @param  array{asset_id: string, price: numeric-string|float|int}  $data
     */
    public function execute(User $user, array $data): MarketItem
    {
        return DB::transaction(function () use ($user, $data): MarketItem {
            $tradeUrl = $user->trade_url !== null ? trim((string) $user->trade_url) : '';
            if ($tradeUrl === '') {
                throw ValidationException::withMessages([
                    'trade_url' => 'Укажите trade-ссылку Steam в личном кабинете — без неё нельзя выставить предмет на продажу.',
                ]);
            }

            if ($this->steamPlayerBans->isEnabled()
                && $this->steamPlayerBans->isEconomyTradeBanned($user->steam_id)) {
                throw ValidationException::withMessages([
                    'asset_id' => 'На вашем аккаунте Steam действует ограничение обмена (trade ban). Продажа с маркета недоступна.',
                ]);
            }

            $asset = $this->steamInventory->findAsset($user->steam_id, $data['asset_id']);
            if ($asset === null) {
                throw ValidationException::withMessages([
                    'asset_id' => 'Этот предмет не найден в вашем инвентаре Steam или не подходит для продажи.',
                ]);
            }

            $exists = MarketItem::query()
                ->where('seller_id', $user->id)
                ->where('asset_id', $asset->assetId)
                ->active()
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'asset_id' => 'Этот предмет уже выставлен на продажу.',
                ]);
            }

            return MarketItem::query()->create([
                'seller_id' => $user->id,
                'asset_id' => $asset->assetId,
                'name' => $asset->name,
                'image_url' => $asset->imageUrl,
                'wear' => $asset->wear,
                'float_value' => $asset->floatValue,
                'rarity' => $asset->rarity,
                'category' => $asset->category,
                'price' => $data['price'],
                'status' => MarketItemStatus::Active,
            ]);
        });
    }
}
