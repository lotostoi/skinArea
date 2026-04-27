<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BalanceType;
use App\Enums\DealStatus;
use App\Enums\MarketItemStatus;
use App\Models\Balance;
use App\Models\Deal;
use App\Models\MarketItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartPurchaseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_cart_returns_deal_resource_and_moves_funds_to_hold(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        Balance::query()->create([
            'user_id' => $buyer->id,
            'type' => BalanceType::Main,
            'amount' => '500.00',
        ]);
        Balance::query()->create([
            'user_id' => $buyer->id,
            'type' => BalanceType::Hold,
            'amount' => '0.00',
        ]);

        $item = MarketItem::factory()->for($seller, 'seller')->create([
            'price' => '120.00',
            'status' => MarketItemStatus::Active,
        ]);

        Sanctum::actingAs($buyer);

        $response = $this->postJson('/api/v1/cart/purchase', [
            'market_item_ids' => [$item->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.0.market_item.id', $item->id)
            ->assertJsonPath('data.0.status', DealStatus::Paid->value);

        $this->assertDatabaseHas('market_items', [
            'id' => $item->id,
            'status' => MarketItemStatus::Reserved->value,
        ]);

        $deal = Deal::query()->firstOrFail();
        $this->assertSame($buyer->id, (int) $deal->buyer_id);

        $this->assertDatabaseHas('balances', [
            'user_id' => $buyer->id,
            'type' => BalanceType::Main->value,
            'amount' => '380.00',
        ]);
        $this->assertDatabaseHas('balances', [
            'user_id' => $buyer->id,
            'type' => BalanceType::Hold->value,
            'amount' => '120.00',
        ]);
    }
}
