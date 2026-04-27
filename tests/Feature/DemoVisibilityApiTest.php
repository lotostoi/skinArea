<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DealStatus;
use App\Enums\ItemCategory;
use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Enums\MarketItemStatus;
use App\Enums\UserRole;
use App\Models\CaseCategory;
use App\Models\CaseLevel;
use App\Models\Deal;
use App\Models\GameCase;
use App\Models\MarketItem;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\DemoDataMarkers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DemoVisibilityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_endpoint_returns_show_demo_data_true_by_default(): void
    {
        $response = $this->getJson('/api/v1/site');

        $response->assertOk()
            ->assertJsonPath('data.show_demo_data', true);
    }

    public function test_guest_market_hides_demo_listing_when_show_demo_data_is_false(): void
    {
        SiteSetting::setShowDemoData(false);

        $seller = User::factory()->create([
            'steam_id' => DemoDataMarkers::STEAM_ID_PREFIX.'765611980000099',
            'username' => DemoDataMarkers::USERNAME_PREFIX.'99',
            'role' => UserRole::User,
        ]);

        MarketItem::query()->create([
            'seller_id' => $seller->id,
            'asset_id' => DemoDataMarkers::LISTING_ASSET_PREFIX.'test_1',
            'name' => 'Demo Listing',
            'image_url' => '/x.png',
            'wear' => ItemWear::FT,
            'float_value' => '0.15',
            'rarity' => ItemRarity::MilSpec,
            'category' => ItemCategory::Rifles,
            'price' => 10.00,
            'status' => MarketItemStatus::Active,
        ]);

        $response = $this->getJson('/api/v1/market/items');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_guest_cases_hides_demo_category_when_show_demo_data_is_false(): void
    {
        SiteSetting::setShowDemoData(false);

        $cat = CaseCategory::query()->create([
            'name' => DemoDataMarkers::CASE_CATEGORY_NAME,
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $case = GameCase::query()->create([
            'name' => 'Hidden Demo Case',
            'image_url' => '/c.png',
            'price' => 50.00,
            'category_id' => $cat->id,
            'sort_order' => 1,
            'is_active' => false,
            'is_featured_on_home' => false,
            'is_manual_admin_case' => false,
        ]);
        CaseLevel::query()->create([
            'case_id' => $case->id,
            'level' => 1,
            'name' => 'L1',
            'chance' => '100.00',
            'prize_amount' => '25.00',
        ]);
        $case->update(['is_active' => true]);

        $response = $this->getJson('/api/v1/cases');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_guest_cases_hides_imported_catalog_case_when_show_demo_data_is_false(): void
    {
        SiteSetting::setShowDemoData(false);

        $cat = CaseCategory::query()->create([
            'name' => DemoDataMarkers::IMPORTED_CASE_CATEGORY_NAME,
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $case = GameCase::query()->create([
            'name' => 'Imported Crate',
            'image_url' => '/c.png',
            'price' => 10.00,
            'category_id' => $cat->id,
            'sort_order' => 1,
            'is_active' => false,
            'is_featured_on_home' => false,
            'is_manual_admin_case' => false,
        ]);
        CaseLevel::query()->create([
            'case_id' => $case->id,
            'level' => 1,
            'name' => 'L1',
            'chance' => '100.00',
            'prize_amount' => '5.00',
        ]);
        $case->update(['is_active' => true]);

        $response = $this->getJson('/api/v1/cases');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_guest_cases_lists_manual_admin_case_when_show_demo_data_is_false(): void
    {
        SiteSetting::setShowDemoData(false);

        $cat = CaseCategory::query()->create([
            'name' => 'Ручные кейсы',
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $case = GameCase::query()->create([
            'name' => 'Admin Case',
            'image_url' => '/c.png',
            'price' => 10.00,
            'category_id' => $cat->id,
            'sort_order' => 1,
            'is_active' => false,
            'is_featured_on_home' => false,
            'is_manual_admin_case' => true,
        ]);
        CaseLevel::query()->create([
            'case_id' => $case->id,
            'level' => 1,
            'name' => 'L1',
            'chance' => '100.00',
            'prize_amount' => '5.00',
        ]);
        $case->update(['is_active' => true]);

        $response = $this->getJson('/api/v1/cases');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Admin Case', $response->json('data.0.name'));
    }

    public function test_guest_cases_lists_manual_case_even_in_demo_named_category_when_demo_hidden(): void
    {
        SiteSetting::setShowDemoData(false);

        $cat = CaseCategory::query()->create([
            'name' => DemoDataMarkers::CASE_CATEGORY_NAME,
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $case = GameCase::query()->create([
            'name' => 'Admin Case In Demo Category',
            'image_url' => '/c.png',
            'price' => 10.00,
            'category_id' => $cat->id,
            'sort_order' => 1,
            'is_active' => false,
            'is_featured_on_home' => false,
            'is_manual_admin_case' => true,
        ]);
        CaseLevel::query()->create([
            'case_id' => $case->id,
            'level' => 1,
            'name' => 'L1',
            'chance' => '100.00',
            'prize_amount' => '5.00',
        ]);
        $case->update(['is_active' => true]);

        $this->getJson('/api/v1/cases')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Admin Case In Demo Category');

        $this->getJson("/api/v1/cases/{$case->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Admin Case In Demo Category');
    }

    public function test_site_endpoint_reflects_false_after_set_show_demo_data(): void
    {
        SiteSetting::setShowDemoData(false);

        $this->getJson('/api/v1/site')
            ->assertOk()
            ->assertJsonPath('data.show_demo_data', false);
    }

    public function test_authenticated_deals_index_hides_demo_deal_when_flag_off(): void
    {
        SiteSetting::setShowDemoData(false);

        $buyer = User::factory()->create();
        $seller = User::factory()->create([
            'steam_id' => DemoDataMarkers::STEAM_ID_PREFIX.'765611980000088',
            'username' => DemoDataMarkers::USERNAME_PREFIX.'88',
        ]);

        $item = MarketItem::query()->create([
            'seller_id' => $seller->id,
            'asset_id' => DemoDataMarkers::LISTING_ASSET_PREFIX.'deal_item',
            'name' => 'Lot',
            'image_url' => '/x.png',
            'wear' => ItemWear::FT,
            'float_value' => '0.1',
            'rarity' => ItemRarity::MilSpec,
            'category' => ItemCategory::Rifles,
            'price' => 5.00,
            'status' => MarketItemStatus::Sold,
        ]);

        Deal::query()->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'market_item_id' => $item->id,
            'price' => '5.00',
            'commission' => '0.25',
            'status' => DealStatus::Completed,
            'trade_offer_id' => null,
            'cancelled_reason' => null,
            'expires_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($buyer);

        $response = $this->getJson('/api/v1/deals');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }
}
