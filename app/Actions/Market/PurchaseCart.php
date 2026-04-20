<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\DealStatus;
use App\Enums\MarketItemStatus;
use App\Models\Deal;
use App\Models\MarketItem;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Эмуляция покупки без Steam: списывает сумму корзины с main-баланса покупателя в hold,
 * создаёт Deal(status=paid) и переводит лот в Reserved. Авто-завершение через 7 дней
 * делает команда deals:settle-due (docs/ledger/purchase-emulation.md).
 */
class PurchaseCart
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    /**
     * @param  array<int, int>  $marketItemIds
     * @return array<int, Deal>
     */
    public function execute(User $buyer, array $marketItemIds): array
    {
        if ($marketItemIds === []) {
            throw new InvalidArgumentException('Корзина пуста.');
        }

        $commissionPercent = (float) config('skinsarena.marketplace.commission', 5.0);
        $holdDays = (int) config('skinsarena.marketplace.purchase_hold_days', 7);

        return DB::transaction(function () use ($buyer, $marketItemIds, $commissionPercent, $holdDays): array {
            $items = MarketItem::query()
                ->whereIn('id', $marketItemIds)
                ->lockForUpdate()
                ->get();

            if ($items->count() !== count(array_unique($marketItemIds))) {
                throw new InvalidArgumentException('Не все позиции корзины найдены.');
            }

            foreach ($items as $item) {
                if ($item->status !== MarketItemStatus::Active) {
                    throw new InvalidArgumentException(sprintf('Лот #%d недоступен для покупки.', $item->id));
                }

                if ((int) $item->seller_id === (int) $buyer->id) {
                    throw new InvalidArgumentException('Нельзя покупать собственные лоты.');
                }
            }

            $expiresAt = Carbon::now()->addDays($holdDays);
            $deals = [];

            foreach ($items as $item) {
                $price = (string) $item->price;
                $commission = bcmul($price, (string) ($commissionPercent / 100), 2);

                $deal = Deal::query()->create([
                    'buyer_id' => $buyer->id,
                    'seller_id' => $item->seller_id,
                    'market_item_id' => $item->id,
                    'price' => $price,
                    'commission' => $commission,
                    'status' => DealStatus::Paid,
                    'trade_offer_id' => null,
                    'cancelled_reason' => null,
                    'expires_at' => $expiresAt,
                ]);

                $this->ledger->hold(
                    user: $buyer,
                    amount: $price,
                    reference: $deal,
                    metadata: ['deal_id' => $deal->id, 'market_item_id' => $item->id],
                );

                $item->update(['status' => MarketItemStatus::Reserved]);

                $deals[] = $deal->fresh();
            }

            return $deals;
        });
    }
}
