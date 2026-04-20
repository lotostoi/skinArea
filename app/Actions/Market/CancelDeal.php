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
 * Отмена сделки: возврат удержанных денег покупателю из hold → main,
 * лот снова Active, сделка Cancelled с указанной причиной.
 */
class CancelDeal
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function execute(Deal $deal, string $reason): void
    {
        DB::transaction(function () use ($deal, $reason): void {
            $locked = Deal::query()->whereKey($deal->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== DealStatus::Paid) {
                throw new InvalidArgumentException(sprintf(
                    'Отменить можно только сделку в статусе paid (#%d: %s).',
                    $locked->id,
                    $locked->status->value,
                ));
            }

            $this->ledger->release(
                user: $locked->buyer,
                amount: (string) $locked->price,
                reference: $locked,
                metadata: ['deal_id' => $locked->id, 'cancel_reason' => $reason],
            );

            $locked->update([
                'status' => DealStatus::Cancelled,
                'cancelled_reason' => $reason,
            ]);

            if ($locked->market_item_id !== null) {
                $locked->marketItem()
                    ->update(['status' => MarketItemStatus::Active]);
            }
        });
    }
}
