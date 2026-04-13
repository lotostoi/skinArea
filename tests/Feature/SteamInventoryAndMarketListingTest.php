<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MarketItemStatus;
use App\Models\MarketItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SteamInventoryAndMarketListingTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_TRADE_URL = 'https://steamcommunity.com/tradeoffer/new/?partner=12345&token=abc123def';

    /**
     * @return array<string, mixed>
     */
    private function sampleSteamPayload(): array
    {
        return [
            'success' => 1,
            'assets' => [
                ['assetid' => '987654321', 'classid' => '111', 'instanceid' => '0', 'amount' => '1'],
            ],
            'descriptions' => [
                [
                    'classid' => '111',
                    'instanceid' => '0',
                    'market_hash_name' => 'AK-47 | Redline (Field-Tested)',
                    'icon_url' => 'abc',
                    'tradable' => 1,
                    'tags' => [
                        ['category' => 'Exterior', 'internal_name' => 'WearCategory2'],
                        ['category' => 'Rarity', 'localized_tag_name' => 'Classified'],
                        ['category' => 'Type', 'localized_tag_name' => 'Rifle'],
                    ],
                ],
            ],
        ];
    }

    public function test_inventory_returns_normalized_items(): void
    {
        config(['skinsarena.steam_inventory.only_tradable' => true]);

        Http::fake([
            'steamcommunity.com/*' => Http::response($this->sampleSteamPayload()),
        ]);

        $user = User::factory()->create([
            'steam_id' => '76561198000000001',
            'trade_url' => self::VALID_TRADE_URL,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/inventory/steam');

        $response->assertOk()
            ->assertJsonPath('data.0.asset_id', '987654321')
            ->assertJsonPath('data.0.name', 'AK-47 | Redline (Field-Tested)')
            ->assertJsonPath('data.0.tradable', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.only_tradable', true)
            ->assertJsonPath('meta.steam_raw_assets', 1)
            ->assertJsonPath('meta.mapped_items', 1);
    }

    public function test_inventory_matches_when_asset_instanceid_is_empty_string(): void
    {
        config(['skinsarena.steam_inventory.only_tradable' => false]);

        $payload = [
            'success' => 1,
            'assets' => [
                ['assetid' => '111', 'classid' => '222', 'instanceid' => '', 'amount' => '1'],
            ],
            'descriptions' => [
                [
                    'classid' => '222',
                    'instanceid' => '0',
                    'name' => 'Empty instance asset',
                    'tradable' => 0,
                    'tags' => [],
                ],
            ],
        ];

        Http::fake([
            'steamcommunity.com/*' => Http::response($payload),
        ]);

        $user = User::factory()->create([
            'steam_id' => '76561198000000002',
            'trade_url' => self::VALID_TRADE_URL,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/inventory/steam')
            ->assertOk()
            ->assertJsonPath('data.0.asset_id', '111')
            ->assertJsonPath('data.0.name', 'Empty instance asset');
    }

    public function test_inventory_includes_untradable_when_only_tradable_disabled(): void
    {
        config(['skinsarena.steam_inventory.only_tradable' => false]);

        $payload = $this->sampleSteamPayload();
        $payload['descriptions'][0]['tradable'] = 0;

        Http::fake([
            'steamcommunity.com/*' => Http::response($payload),
        ]);

        $user = User::factory()->create([
            'steam_id' => '76561198000000001',
            'trade_url' => self::VALID_TRADE_URL,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/inventory/steam');

        $response->assertOk()
            ->assertJsonPath('data.0.tradable', false)
            ->assertJsonPath('meta.only_tradable', false)
            ->assertJsonPath('meta.steam_raw_assets', 1)
            ->assertJsonPath('meta.mapped_items', 1);
    }

    public function test_store_creates_market_item(): void
    {
        Http::fake([
            'steamcommunity.com/*' => Http::response($this->sampleSteamPayload()),
        ]);

        $user = User::factory()->create([
            'steam_id' => '76561198000000001',
            'trade_url' => self::VALID_TRADE_URL,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/market/items', [
            'asset_id' => '987654321',
            'price' => 12.5,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.asset_id', '987654321');

        $this->assertDatabaseHas('market_items', [
            'seller_id' => $user->id,
            'asset_id' => '987654321',
            'status' => 'active',
        ]);
    }

    public function test_store_duplicate_active_asset_returns_422(): void
    {
        Http::fake([
            'steamcommunity.com/*' => Http::response($this->sampleSteamPayload()),
        ]);

        $user = User::factory()->create([
            'steam_id' => '76561198000000001',
            'trade_url' => self::VALID_TRADE_URL,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/market/items', [
            'asset_id' => '987654321',
            'price' => 10,
        ])->assertCreated();

        $this->postJson('/api/v1/market/items', [
            'asset_id' => '987654321',
            'price' => 20,
        ])->assertStatus(422);
    }

    public function test_destroy_cancels_active_listing(): void
    {
        $user = User::factory()->create();
        $item = MarketItem::factory()->for($user, 'seller')->create([
            'status' => MarketItemStatus::Active,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/market/items/'.$item->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(
            MarketItemStatus::Cancelled,
            $item->fresh()->status,
        );
    }

    public function test_destroy_other_users_listing_forbidden(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $item = MarketItem::factory()->for($owner, 'seller')->create([
            'status' => MarketItemStatus::Active,
        ]);

        Sanctum::actingAs($other);

        $this->deleteJson('/api/v1/market/items/'.$item->id)->assertForbidden();
    }

    public function test_index_lists_active_items_only(): void
    {
        MarketItem::factory()->create(['status' => MarketItemStatus::Active]);
        MarketItem::factory()->create(['status' => MarketItemStatus::Cancelled]);

        $response = $this->getJson('/api/v1/market/items');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
