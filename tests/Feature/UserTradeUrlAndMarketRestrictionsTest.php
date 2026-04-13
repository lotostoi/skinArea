<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTradeUrlAndMarketRestrictionsTest extends TestCase
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
                        ['category' => 'Type', 'localized_tag_name' => 'Rifle'],
                    ],
                ],
            ],
        ];
    }

    public function test_inventory_returns_422_when_trade_url_missing(): void
    {
        Http::fake([
            'steamcommunity.com/*' => Http::response($this->sampleSteamPayload()),
        ]);

        $user = User::factory()->create([
            'steam_id' => '76561198000000001',
            'trade_url' => null,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/inventory/steam')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Укажите trade-ссылку Steam в разделе настроек маркета, чтобы просматривать инвентарь.');
    }

    public function test_store_returns_422_when_trade_url_missing(): void
    {
        Http::fake([
            'steamcommunity.com/*' => Http::response($this->sampleSteamPayload()),
        ]);

        $user = User::factory()->create([
            'steam_id' => '76561198000000001',
            'trade_url' => null,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/market/items', [
            'asset_id' => '987654321',
            'price' => 12.5,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['trade_url']);
    }

    public function test_user_can_patch_trade_url(): void
    {
        $user = User::factory()->create(['trade_url' => null]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/user/trade-url', [
            'trade_url' => self::VALID_TRADE_URL,
        ])
            ->assertOk()
            ->assertJsonPath('data.trade_url', self::VALID_TRADE_URL);

        $this->assertSame(self::VALID_TRADE_URL, $user->fresh()->trade_url);
    }

    public function test_patch_trade_url_validates_steam_link(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/user/trade-url', [
            'trade_url' => 'https://example.com/foo',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['trade_url']);
    }

    public function test_store_blocked_when_steam_economy_ban(): void
    {
        config([
            'skinsarena.steam_web_api.key' => 'test-key',
            'skinsarena.steam_web_api.trade_ban_check_enabled' => true,
        ]);

        Http::fake([
            'steamcommunity.com/*' => Http::response($this->sampleSteamPayload()),
            'api.steampowered.com/*' => Http::response([
                'players' => [
                    [
                        'SteamId' => '76561198000000001',
                        'CommunityBanned' => false,
                        'VACBanned' => false,
                        'EconomyBan' => 'banned',
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create([
            'steam_id' => '76561198000000001',
            'trade_url' => self::VALID_TRADE_URL,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/market/items', [
            'asset_id' => '987654321',
            'price' => 12.5,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['asset_id']);
    }
}
