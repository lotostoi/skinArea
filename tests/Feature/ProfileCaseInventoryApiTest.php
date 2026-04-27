<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CaseOpeningStatus;
use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Models\CaseCategory;
use App\Models\CaseItem;
use App\Models\CaseLevel;
use App\Models\CaseOpening;
use App\Models\GameCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileCaseInventoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_index_supports_status_filter_and_search(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $gameCase = $this->createCaseFixture();

        $soldOpening = $this->createOpening(
            user: $user,
            gameCase: $gameCase,
            itemName: 'AK-47 Redline',
            wonItemPrice: '120.00',
            status: CaseOpeningStatus::Sold,
        );
        $this->createOpening(
            user: $user,
            gameCase: $gameCase,
            itemName: 'AWP Asiimov',
            wonItemPrice: '300.00',
            status: CaseOpeningStatus::InInventory,
        );
        $this->createOpening(
            user: $otherUser,
            gameCase: $gameCase,
            itemName: 'AK-47 Other User',
            wonItemPrice: '500.00',
            status: CaseOpeningStatus::Sold,
        );

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/profile/case-inventory?status=sold&search=redline');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $soldOpening->id)
            ->assertJsonPath('data.0.source', 'case_open')
            ->assertJsonPath('data.0.status', CaseOpeningStatus::Sold->value);
    }

    public function test_inventory_index_supports_sorting_by_won_price(): void
    {
        $user = User::factory()->create();
        $gameCase = $this->createCaseFixture();

        $cheap = $this->createOpening(
            user: $user,
            gameCase: $gameCase,
            itemName: 'Cheap',
            wonItemPrice: '50.00',
            status: CaseOpeningStatus::InInventory,
        );
        $this->createOpening(
            user: $user,
            gameCase: $gameCase,
            itemName: 'Expensive',
            wonItemPrice: '400.00',
            status: CaseOpeningStatus::InInventory,
        );

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/profile/case-inventory?sort=won_item_price&order=asc');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $cheap->id)
            ->assertJsonPath('data.0.won_item_price', '50.00');
    }

    public function test_inventory_summary_returns_aggregates(): void
    {
        $user = User::factory()->create();
        $gameCase = $this->createCaseFixture();

        $this->createOpening(
            user: $user,
            gameCase: $gameCase,
            itemName: 'In Inventory',
            wonItemPrice: '150.00',
            status: CaseOpeningStatus::InInventory,
        );
        $this->createOpening(
            user: $user,
            gameCase: $gameCase,
            itemName: 'Sold Item',
            wonItemPrice: '90.00',
            status: CaseOpeningStatus::Sold,
        );
        $this->createOpening(
            user: $user,
            gameCase: $gameCase,
            itemName: 'Withdrawn Item',
            wonItemPrice: '60.00',
            status: CaseOpeningStatus::Withdrawn,
        );
        $this->createOpening(
            user: $user,
            gameCase: $gameCase,
            itemName: 'Upgrade Item',
            wonItemPrice: '40.00',
            status: CaseOpeningStatus::UsedInUpgrade,
        );

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/profile/case-inventory/summary');

        $response->assertOk()
            ->assertJsonPath('data.total_items', 4)
            ->assertJsonPath('data.in_inventory_items', 1)
            ->assertJsonPath('data.sold_items', 1)
            ->assertJsonPath('data.withdrawn_items', 1)
            ->assertJsonPath('data.used_in_upgrade_items', 1);

        $summary = $response->json('data');
        $this->assertSame(340.0, (float) $summary['total_value']);
        $this->assertSame(150.0, (float) $summary['in_inventory_value']);
    }

    private function createCaseFixture(): GameCase
    {
        $category = CaseCategory::query()->create([
            'name' => 'Profile Cases',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        return GameCase::query()->create([
            'name' => 'Profile Case',
            'description' => null,
            'shadow_color' => null,
            'image_url' => null,
            'price' => '100.00',
            'category_id' => $category->id,
            'sort_order' => 1,
            'is_active' => false,
            'is_featured_on_home' => false,
        ]);
    }

    private function createOpening(
        User $user,
        GameCase $gameCase,
        string $itemName,
        string $wonItemPrice,
        CaseOpeningStatus $status,
    ): CaseOpening {
        static $levelValue = 1;

        $level = CaseLevel::query()->create([
            'case_id' => $gameCase->id,
            'level' => $levelValue++,
            'name' => $itemName.' Level',
            'chance' => '100.00',
            'prize_amount' => $wonItemPrice,
        ]);

        $item = CaseItem::query()->create([
            'case_level_id' => $level->id,
            'name' => $itemName,
            'image_url' => null,
            'price' => $wonItemPrice,
            'wear' => ItemWear::FN,
            'rarity' => ItemRarity::MilSpec,
        ]);

        return CaseOpening::query()->create([
            'user_id' => $user->id,
            'case_id' => $gameCase->id,
            'case_item_id' => $item->id,
            'cost' => '100.00',
            'won_item_price' => $wonItemPrice,
            'status' => $status,
        ]);
    }
}
