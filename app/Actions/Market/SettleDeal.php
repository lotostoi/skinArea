<?php

declare(strict_types=1);

namespace App\Actions\Market;

use App\Enums\DealStatus;
use App\Enums\MarketItemStatus;
use App\Models\Deal;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Завершает сделку: hold(buyer) → main(seller) + комиссия платформе.
 * Используется фоновой командой deals:settle-due и может быть вызвана вручную.
 */
class SettleDeal
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function execute(Deal $deal): void
    {
        DB::transaction(function () use ($deal): void {
            $locked = Deal::query()->whereKey($deal->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== DealStatus::Paid) {
                throw new InvalidArgumentException(sprintf(
                    'Сделка #%d не в статусе paid (статус: %s).',
                    $locked->id,
                    $locked->status->value,
                ));
            }

            $this->ledger->transferHoldToSeller($locked);

            $locked->update([
                'status' => DealStatus::Completed,
            ]);

            if ($locked->market_item_id !== null) {
                $locked->marketItem()
                    ->update(['status' => MarketItemStatus::Sold]);
            }
        });
    }
}
