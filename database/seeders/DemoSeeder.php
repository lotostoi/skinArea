<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BalanceType;
use App\Enums\DealStatus;
use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Enums\MarketItemStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\Balance;
use App\Models\CaseCategory;
use App\Models\CaseItem;
use App\Models\CaseLevel;
use App\Models\Deal;
use App\Models\GameCase;
use App\Models\MarketItem;
use App\Models\User;
use App\Services\Ledger\Dto\CreateEntryDto;
use App\Services\LedgerService;
use Database\Seeders\Demo\DemoSkinCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Демо-сидер витрин. НЕ подключается в DatabaseSeeder.
 * Все данные помечены префиксом demo_ и удаляются командой php artisan demo:wipe.
 */
class DemoSeeder extends Seeder
{
    public const STEAM_ID_PREFIX = 'demo_';

    public const USERNAME_PREFIX = 'demo_seller_';

    public const CASE_CATEGORY_NAME = 'Демо-кейсы';

    public const LISTING_ASSET_PREFIX = 'demo_asset_';

    public const SOLD_ASSET_PREFIX = 'demo_sold_';

    public function run(): void
    {
        $sellers = $this->seedSellers();
        $this->seedMarketItems($sellers);
        $categoryId = $this->seedCaseCategory();
        $this->seedCases($categoryId);
        $this->seedSoldHistory($sellers);
    }

    /**
     * @return list<User>
     */
    private function seedSellers(): array
    {
        $definitions = [
            ['username' => self::USERNAME_PREFIX.'1', 'suffix' => '01', 'avatar_seed' => 'demo-1'],
            ['username' => self::USERNAME_PREFIX.'2', 'suffix' => '02', 'avatar_seed' => 'demo-2'],
            ['username' => self::USERNAME_PREFIX.'3', 'suffix' => '03', 'avatar_seed' => 'demo-3'],
            ['username' => self::USERNAME_PREFIX.'4', 'suffix' => '04', 'avatar_seed' => 'demo-4'],
        ];

        $sellers = [];

        foreach ($definitions as $def) {
            $steamId = self::STEAM_ID_PREFIX.'765611980000'.$def['suffix'];

            $user = User::query()->updateOrCreate(
                ['steam_id' => $steamId],
                [
                    'username' => $def['username'],
                    'avatar_url' => 'https://api.dicebear.com/7.x/bottts/png?seed='.$def['avatar_seed'],
                    'trade_url' => 'https://steamcommunity.com/tradeoffer/new/?partner=0&token=demo',
                    'email' => null,
                    'email_verified_at' => null,
                    'password' => null,
                    'role' => UserRole::User,
                    'is_banned' => false,
                ],
            );

            Balance::query()->updateOrCreate(
                ['user_id' => $user->id, 'type' => BalanceType::Main],
                ['amount' => 25000.00],
            );
            Balance::query()->updateOrCreate(
                ['user_id' => $user->id, 'type' => BalanceType::Hold],
                ['amount' => 0.00],
            );

            $sellers[] = $user;
        }

        return $sellers;
    }

    /**
     * @param  list<User>  $sellers
     */
    private function seedMarketItems(array $sellers): void
    {
        $skins = DemoSkinCatalog::skins();
        $wears = ItemWear::cases();

        $index = 0;
        foreach ($skins as $skin) {
            $wearsForSkin = $this->wearsForRarity($skin['rarity']);

            foreach ($wearsForSkin as $wear) {
                $seller = $sellers[$index % count($sellers)];
                $price = $this->priceFor($skin['base_price'], $wear);
                $assetId = self::LISTING_ASSET_PREFIX.Str::lower($skin['weapon']).'_'.Str::slug($skin['skin']).'_'.$wear->value.'_'.$index;

                MarketItem::query()->updateOrCreate(
                    ['seller_id' => $seller->id, 'asset_id' => $assetId],
                    [
                        'name' => $skin['weapon'].' | '.$skin['skin'],
                        'image_url' => $skin['image'],
                        'wear' => $wear,
                        'float_value' => $this->floatForWear($wear),
                        'rarity' => $skin['rarity'],
                        'category' => $skin['category'],
                        'price' => $price,
                        'status' => MarketItemStatus::Active,
                    ],
                );

                $index++;
            }
        }
    }

    private function seedCaseCategory(): int
    {
        $category = CaseCategory::query()->updateOrCreate(
            ['name' => self::CASE_CATEGORY_NAME],
            ['sort_order' => 0, 'is_visible' => true],
        );

        return (int) $category->id;
    }

    private function seedCases(int $categoryId): void
    {
        $cases = DemoSkinCatalog::cases();
        $skinPool = DemoSkinCatalog::skins();

        foreach ($cases as $sort => $caseDef) {
            $case = GameCase::query()->updateOrCreate(
                ['name' => $caseDef['name'], 'category_id' => $categoryId],
                [
                    'image_url' => $caseDef['image'],
                    'price' => $caseDef['price'],
                    'sort_order' => $sort,
                    'is_active' => true,
                    'is_featured_on_home' => $sort < 8,
                ],
            );

            $case->levels()->delete();

            $levels = [
                ['level' => 1, 'name' => 'Армейское', 'chance' => 79.9224, 'rarity' => ItemRarity::MilSpec],
                ['level' => 2, 'name' => 'Запрещённое', 'chance' => 15.9847, 'rarity' => ItemRarity::Restricted],
                ['level' => 3, 'name' => 'Засекреченное', 'chance' => 3.1969, 'rarity' => ItemRarity::Classified],
                ['level' => 4, 'name' => 'Тайное', 'chance' => 0.6395, 'rarity' => ItemRarity::Covert],
                ['level' => 5, 'name' => 'Нож/Перчатки', 'chance' => 0.2565, 'rarity' => ItemRarity::Covert],
            ];

            foreach ($levels as $lvl) {
                $level = CaseLevel::query()->create([
                    'case_id' => $case->id,
                    'level' => $lvl['level'],
                    'name' => $lvl['name'],
                    'chance' => $lvl['chance'],
                ]);

                $pool = array_values(array_filter(
                    $skinPool,
                    fn (array $s): bool => $this->matchesRarityTier($s['rarity'], $lvl['rarity']),
                ));

                if ($pool === []) {
                    $pool = $skinPool;
                }

                $picks = array_slice($this->shuffleCopy($pool), 0, 5);

                foreach ($picks as $skin) {
                    $wear = $this->randomWearForRarity($skin['rarity']);
                    CaseItem::query()->create([
                        'case_level_id' => $level->id,
                        'name' => $skin['weapon'].' | '.$skin['skin'],
                        'image_url' => $skin['image'],
                        'price' => $this->priceFor($skin['base_price'], $wear),
                        'wear' => $wear,
                        'rarity' => $skin['rarity'],
                    ]);
                }
            }
        }
    }

    /**
     * @param  list<User>  $sellers
     */
    private function seedSoldHistory(array $sellers): void
    {
        $skins = DemoSkinCatalog::skins();
        $buyer = $sellers[0];

        $statuses = [
            DealStatus::Completed,
            DealStatus::Completed,
            DealStatus::TradeAccepted,
            DealStatus::TradeSent,
            DealStatus::Paid,
            DealStatus::Cancelled,
        ];

        foreach ($statuses as $i => $status) {
            $skin = $skins[array_rand($skins)];
            $seller = $sellers[($i + 1) % count($sellers)];
            $wear = ItemWear::FT;
            $price = $this->priceFor($skin['base_price'], $wear);
            $commission = round($price * 0.05, 2);

            $marketItemStatus = $status === DealStatus::Cancelled
                ? MarketItemStatus::Cancelled
                : MarketItemStatus::Sold;

            $assetId = self::SOLD_ASSET_PREFIX.$i;
            $item = MarketItem::query()->updateOrCreate(
                ['seller_id' => $seller->id, 'asset_id' => $assetId],
                [
                    'name' => $skin['weapon'].' | '.$skin['skin'],
                    'image_url' => $skin['image'],
                    'wear' => $wear,
                    'float_value' => $this->floatForWear($wear),
                    'rarity' => $skin['rarity'],
                    'category' => $skin['category'],
                    'price' => $price,
                    'status' => $marketItemStatus,
                ],
            );

            $existingDeal = Deal::query()
                ->where('market_item_id', $item->id)
                ->first();

            if ($existingDeal !== null) {
                continue;
            }

            $createdAt = Carbon::now()->subDays(random_int(1, 30));
            $deal = Deal::query()->create([
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'market_item_id' => $item->id,
                'price' => $price,
                'commission' => $commission,
                'status' => $status,
                'trade_offer_id' => $status === DealStatus::Cancelled ? null : 'demo_trade_'.$i,
                'cancelled_reason' => $status === DealStatus::Cancelled ? 'Демо-отмена' : null,
                'expires_at' => $createdAt->copy()->addMinutes(15),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if ($status === DealStatus::Completed) {
                $ledger = app(LedgerService::class);
                $dto = (new CreateEntryDto)
                    ->setUserId((int) $seller->id)
                    ->setType(TransactionType::Sale)
                    ->setBalanceType(BalanceType::Main)
                    ->setAmount((string) ($price - $commission))
                    ->setReference($deal)
                    ->setIdempotencyKey('demo-sale-'.$deal->id)
                    ->setMetadata(['demo' => true, 'commission' => $commission]);

                $pending = $ledger->createPending($dto);
                $ledger->post($pending);
            }
        }
    }

    /**
     * @return list<ItemWear>
     */
    private function wearsForRarity(ItemRarity $rarity): array
    {
        return match ($rarity) {
            ItemRarity::Contraband => [ItemWear::FT, ItemWear::MW],
            ItemRarity::Covert => [ItemWear::FN, ItemWear::MW, ItemWear::FT],
            ItemRarity::Classified => [ItemWear::MW, ItemWear::FT, ItemWear::WW],
            default => [ItemWear::FT, ItemWear::WW, ItemWear::BS],
        };
    }

    private function priceFor(float $basePrice, ItemWear $wear): float
    {
        $modifier = match ($wear) {
            ItemWear::FN => 1.45,
            ItemWear::MW => 1.15,
            ItemWear::FT => 1.00,
            ItemWear::WW => 0.78,
            ItemWear::BS => 0.62,
        };

        $noise = 1.0 + (random_int(-8, 8) / 100);

        return round($basePrice * $modifier * $noise, 2);
    }

    private function floatForWear(ItemWear $wear): float
    {
        [$min, $max] = match ($wear) {
            ItemWear::FN => [0.00, 0.07],
            ItemWear::MW => [0.07, 0.15],
            ItemWear::FT => [0.15, 0.38],
            ItemWear::WW => [0.38, 0.45],
            ItemWear::BS => [0.45, 1.00],
        };

        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 6);
    }

    private function randomWearForRarity(ItemRarity $rarity): ItemWear
    {
        $pool = $this->wearsForRarity($rarity);

        return $pool[array_rand($pool)];
    }

    private function matchesRarityTier(ItemRarity $skinRarity, ItemRarity $tier): bool
    {
        return $skinRarity === $tier;
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @return list<T>
     */
    private function shuffleCopy(array $items): array
    {
        $copy = $items;
        shuffle($copy);

        return $copy;
    }
}
