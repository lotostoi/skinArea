<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Balance;
use App\Models\CaseCategory;
use App\Models\CaseOpening;
use App\Models\Deal;
use App\Models\GameCase;
use App\Models\MarketItem;
use App\Models\Transaction;
use App\Models\Upgrade;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Удаляет демо-данные, созданные DemoSeeder.
 *
 * Маркеры демо-данных:
 * - users: одновременно steam_id LIKE demo_% и username LIKE demo_seller_ (только демо-продавцы сидера);
 * - case_categories.name === DemoSeeder::CASE_CATEGORY_NAME (Демо-кейсы);
 * - market_items / deals / transactions — всё связанное с демо-продавцами.
 *
 * Команда НЕ трогает миграции и файлы в public/.
 */
class DemoWipeCommand extends Command
{
    protected $signature = 'demo:wipe {--force : Не спрашивать подтверждение}';

    protected $description = 'Удалить демо-данные витрины (только demo_seller_* + steam_id demo_*, их лоты/сделки, демо-кейсы).';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Удалить демо-данные (пользователи demo_seller_* с steam_id demo_*, их лоты, сделки, демо-кейсы)?')) {
            $this->info('Отменено.');

            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            $sellerIds = User::query()
                ->where('steam_id', 'like', DemoSeeder::STEAM_ID_PREFIX.'%')
                ->where('username', 'like', DemoSeeder::USERNAME_PREFIX.'%')
                ->pluck('id')
                ->all();

            if ($sellerIds !== []) {
                $marketItemIds = MarketItem::query()
                    ->whereIn('seller_id', $sellerIds)
                    ->pluck('id')
                    ->all();

                $dealIds = Deal::query()
                    ->where(function ($q) use ($sellerIds, $marketItemIds) {
                        $q->whereIn('seller_id', $sellerIds)
                            ->orWhereIn('buyer_id', $sellerIds);
                        if ($marketItemIds !== []) {
                            $q->orWhereIn('market_item_id', $marketItemIds);
                        }
                    })
                    ->pluck('id')
                    ->all();

                if ($dealIds !== []) {
                    Transaction::query()
                        ->where('reference_type', 'deal')
                        ->whereIn('reference_id', $dealIds)
                        ->delete();

                    Deal::query()->whereIn('id', $dealIds)->forceDelete();
                }

                Transaction::query()->whereIn('user_id', $sellerIds)->delete();

                if ($marketItemIds !== []) {
                    MarketItem::query()->whereIn('id', $marketItemIds)->delete();
                }

                CaseOpening::query()->whereIn('user_id', $sellerIds)->delete();
                Upgrade::query()->whereIn('user_id', $sellerIds)->delete();
                Balance::query()->whereIn('user_id', $sellerIds)->delete();

                PersonalAccessToken::query()
                    ->where('tokenable_type', (new User)->getMorphClass())
                    ->whereIn('tokenable_id', $sellerIds)
                    ->delete();

                User::query()->whereIn('id', $sellerIds)->forceDelete();
            }

            $demoCategoryIds = CaseCategory::query()
                ->where('name', DemoSeeder::CASE_CATEGORY_NAME)
                ->pluck('id')
                ->all();

            if ($demoCategoryIds !== []) {
                $caseIds = GameCase::query()
                    ->whereIn('category_id', $demoCategoryIds)
                    ->pluck('id')
                    ->all();

                if ($caseIds !== []) {
                    GameCase::query()->whereIn('id', $caseIds)->delete();
                }

                CaseCategory::query()->whereIn('id', $demoCategoryIds)->delete();
            }
        });

        $this->info('Демо-данные удалены.');

        return self::SUCCESS;
    }
}
