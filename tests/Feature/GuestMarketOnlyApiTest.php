<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CaseCategory;
use App\Models\GameCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestMarketOnlyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_market_items(): void
    {
        $response = $this->getJson('/api/v1/market/items');

        $response->assertOk();
    }

    public function test_guest_can_list_cases_catalog(): void
    {
        $response = $this->getJson('/api/v1/cases');

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_guest_can_list_featured_cases(): void
    {
        $category = CaseCategory::query()->create([
            'name' => 'Тест-категория',
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        GameCase::query()->create([
            'name' => 'На главной',
            'image_url' => '/images/demo/case.png',
            'price' => 199.00,
            'category_id' => $category->id,
            'sort_order' => 1,
            'is_active' => true,
            'is_featured_on_home' => true,
        ]);

        GameCase::query()->create([
            'name' => 'Без главной',
            'image_url' => '/images/demo/case2.png',
            'price' => 99.00,
            'category_id' => $category->id,
            'sort_order' => 2,
            'is_active' => true,
            'is_featured_on_home' => false,
        ]);

        GameCase::query()->create([
            'name' => 'Неактивный на главной',
            'image_url' => '/images/demo/case3.png',
            'price' => 149.00,
            'category_id' => $category->id,
            'sort_order' => 0,
            'is_active' => false,
            'is_featured_on_home' => true,
        ]);

        $response = $this->getJson('/api/v1/cases/featured');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'На главной');
    }

    public function test_guest_gets_unauthorized_on_upgrade_items(): void
    {
        $response = $this->getJson('/api/v1/upgrade/items');

        $response->assertUnauthorized();
    }

    public function test_guest_gets_unauthorized_on_support_tickets(): void
    {
        $response = $this->getJson('/api/v1/support/tickets');

        $response->assertUnauthorized();
    }
}
