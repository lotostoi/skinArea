<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BalanceType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceDepositCallbackSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_callback_rejects_invalid_signature_when_enforced(): void
    {
        config([
            'skinsarena.balance.deposit_callback.require_signature' => true,
            'skinsarena.balance.deposit_callback.secret' => 'top-secret',
            'skinsarena.balance.deposit_callback.signature_header' => 'X-SkinsArena-Signature',
        ]);

        $transaction = $this->createPendingDepositTransaction('callback-key-invalid');

        $response = $this->postJson('/api/v1/balance/deposit/callback', [
            'idempotency_key' => $transaction->idempotency_key,
            'status' => 'succeeded',
        ], [
            'X-SkinsArena-Signature' => 'invalid-signature',
        ]);

        $response->assertStatus(401);
        $this->assertSame(TransactionStatus::Pending, $transaction->fresh()->status);
    }

    public function test_deposit_callback_accepts_valid_signature_when_enforced(): void
    {
        config([
            'skinsarena.balance.deposit_callback.require_signature' => true,
            'skinsarena.balance.deposit_callback.secret' => 'top-secret',
            'skinsarena.balance.deposit_callback.signature_header' => 'X-SkinsArena-Signature',
        ]);

        $transaction = $this->createPendingDepositTransaction('callback-key-valid');
        $payload = [
            'idempotency_key' => $transaction->idempotency_key,
            'status' => 'succeeded',
        ];

        $signature = hash_hmac('sha256', (string) json_encode($payload), 'top-secret');

        $response = $this->postJson('/api/v1/balance/deposit/callback', $payload, [
            'X-SkinsArena-Signature' => $signature,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', TransactionStatus::Posted->value);

        $transaction->refresh();
        $this->assertSame(TransactionStatus::Posted, $transaction->status);
        $this->assertDatabaseHas('balances', [
            'user_id' => $transaction->user_id,
            'type' => BalanceType::Main->value,
            'amount' => '100.00',
        ]);
    }

    public function test_deposit_callback_keeps_backward_compatibility_when_signature_is_disabled(): void
    {
        config([
            'skinsarena.balance.deposit_callback.require_signature' => false,
        ]);

        $transaction = $this->createPendingDepositTransaction('callback-key-legacy');

        $this->postJson('/api/v1/balance/deposit/callback', [
            'idempotency_key' => $transaction->idempotency_key,
            'status' => 'failed',
            'reason' => 'Тестовая ошибка',
        ])->assertOk()
            ->assertJsonPath('data.status', TransactionStatus::Failed->value);
    }

    private function createPendingDepositTransaction(string $idempotencyKey): Transaction
    {
        $user = User::factory()->create();

        Balance::query()->create([
            'user_id' => $user->id,
            'type' => BalanceType::Main,
            'amount' => 0,
        ]);

        return Transaction::query()->create([
            'user_id' => $user->id,
            'type' => TransactionType::Deposit,
            'status' => TransactionStatus::Pending,
            'balance_type' => BalanceType::Main,
            'amount' => '100.00',
            'balance_after' => '0.00',
            'metadata' => [],
            'idempotency_key' => $idempotencyKey,
        ]);
    }
}
