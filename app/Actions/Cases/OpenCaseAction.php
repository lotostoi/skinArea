<?php

declare(strict_types=1);

namespace App\Actions\Cases;

use App\Enums\BalanceType;
use App\Enums\CaseOpeningStatus;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Balance;
use App\Models\CaseItem;
use App\Models\CaseLevel;
use App\Models\CaseOpening;
use App\Models\GameCase;
use App\Models\User;
use App\Services\CasePrizeFundService;
use App\Services\DemoVisibilityService;
use App\Services\Ledger\Dto\CreateEntryDto;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OpenCaseAction
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly CasePrizeFundService $fundService,
        private readonly DemoVisibilityService $demoVisibility,
    ) {}

    public function execute(User $user, GameCase $case): CaseOpening
    {
        abort_if(
            $this->demoVisibility->shouldHideDemo() && $this->demoVisibility->isDemoGameCase($case),
            404,
        );

        return DB::transaction(function () use ($user, $case): CaseOpening {
            $lockedCase = GameCase::query()
                ->whereKey($case->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Заблокировать баланс и проверить достаточность средств
            $balance = Balance::query()
                ->where('user_id', $user->id)
                ->where('type', BalanceType::Main)
                ->lockForUpdate()
                ->first();
            $bonusBalance = Balance::query()
                ->where('user_id', $user->id)
                ->where('type', BalanceType::Bonus)
                ->lockForUpdate()
                ->first();

            $currentAmount = $balance ? (string) $balance->amount : '0';
            $currentBonusAmount = $bonusBalance ? (string) $bonusBalance->amount : '0';
            $price = (string) $lockedCase->price;
            $availableTotal = bcadd($currentAmount, $currentBonusAmount, 2);

            if (bccomp($availableTotal, $price, 2) < 0) {
                throw new InsufficientBalanceException(
                    sprintf(
                        'Недостаточно средств. Нужно %s ₽, доступно %s ₽.',
                        number_format((float) $price, 2, '.', ''),
                        number_format((float) $availableTotal, 2, '.', ''),
                    ),
                );
            }

            $bonusSpent = bccomp($currentBonusAmount, $price, 2) >= 0
                ? $price
                : $currentBonusAmount;
            $mainSpent = bcsub($price, $bonusSpent, 2);

            // 2. Определить доступные уровни по фонду
            $lockedCase->load('levels.items');
            $availableLevels = $this->fundService->getAvailableLevels($lockedCase);

            if ($availableLevels->isEmpty()) {
                throw new RuntimeException('Кейс не настроен: уровни отсутствуют.');
            }

            $guaranteedLevel = $availableLevels->sortByDesc('level')->first();

            // 3. Пересчитать шансы и выбрать уровень
            $chances = $this->fundService->redistributeChances($availableLevels, $guaranteedLevel);
            $wonLevel = $this->rollLevel($chances);

            // 4. Выбрать случайный предмет из уровня
            $wonItem = $this->rollItem($wonLevel);

            if ($wonItem === null) {
                // Если предметов нет — дать гарантированный уровень, если есть предметы
                $wonLevel = $guaranteedLevel;
                $wonItem = $this->rollItem($wonLevel);

                if ($wonItem === null) {
                    throw new RuntimeException('В уровне нет предметов. Наполните кейс через админку.');
                }
            }

            // 5. Создать запись об открытии
            $opening = CaseOpening::query()->create([
                'user_id' => $user->id,
                'case_id' => $lockedCase->id,
                'case_item_id' => $wonItem->id,
                'cost' => $price,
                'won_item_price' => (string) $wonItem->price,
                'status' => CaseOpeningStatus::InInventory,
            ]);

            // 6. Списать бонусный баланс, затем основной
            $baseIdempotencyKey = 'case-open-'.Str::uuid()->toString();
            $metadata = [
                'case_id' => $lockedCase->id,
                'case_name' => $lockedCase->name,
            ];

            if (bccomp($bonusSpent, '0', 2) > 0) {
                $bonusDto = (new CreateEntryDto)
                    ->setUserId($user->id)
                    ->setType(TransactionType::CaseOpen)
                    ->setBalanceType(BalanceType::Bonus)
                    ->setAmount('-'.$bonusSpent)
                    ->setReference($opening)
                    ->setIdempotencyKey($baseIdempotencyKey.'-bonus')
                    ->setMetadata(array_merge($metadata, ['source_balance' => BalanceType::Bonus->value]));

                $this->ledger->post($this->ledger->createPending($bonusDto));
            }

            if (bccomp($mainSpent, '0', 2) > 0) {
                $mainDto = (new CreateEntryDto)
                    ->setUserId($user->id)
                    ->setType(TransactionType::CaseOpen)
                    ->setBalanceType(BalanceType::Main)
                    ->setAmount('-'.$mainSpent)
                    ->setReference($opening)
                    ->setIdempotencyKey($baseIdempotencyKey.'-main')
                    ->setMetadata(array_merge($metadata, ['source_balance' => BalanceType::Main->value]));

                $this->ledger->post($this->ledger->createPending($mainDto));
            }

            return $opening->load(['caseItem', 'gameCase']);
        });
    }

    /**
     * Взвешенный выбор уровня по шансам.
     *
     * @param  array<int, array{level: CaseLevel, chance: string}>  $chances
     */
    private function rollLevel(array $chances): CaseLevel
    {
        $total = array_reduce(
            $chances,
            fn (float $carry, array $entry): float => $carry + (float) $entry['chance'],
            0.0,
        );

        $roll = (mt_rand(0, (int) ($total * 10000)) / 10000);
        $cumulative = 0.0;

        foreach ($chances as $entry) {
            $cumulative += (float) $entry['chance'];
            if ($roll <= $cumulative) {
                return $entry['level'];
            }
        }

        return $chances[array_key_last($chances)]['level'];
    }

    /**
     * Случайный предмет из уровня.
     */
    private function rollItem(CaseLevel $level): ?CaseItem
    {
        $items = $level->items;

        if ($items->isEmpty()) {
            return null;
        }

        return $items->random();
    }
}
