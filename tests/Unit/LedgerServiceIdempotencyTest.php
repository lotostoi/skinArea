<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\BalanceType;
use App\Enums\TransactionType;
use App\Models\User;
use App\Services\Ledger\Dto\CreateEntryDto;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerServiceIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_pending_returns_existing_transaction_for_same_idempotency_key(): void
    {
        $user = User::factory()->create();
        $ledger = app(LedgerService::class);

        $dto = (new CreateEntryDto)
            ->setUserId($user->id)
            ->setType(TransactionType::Deposit)
            ->setBalanceType(BalanceType::Main)
            ->setAmount('25.00')
            ->setMetadata(['source' => 'test'])
            ->setIdempotencyKey('ledger-idempotency-key');

        $first = $ledger->createPending($dto);
        $second = $ledger->createPending($dto);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('transactions', 1);
    }
}
