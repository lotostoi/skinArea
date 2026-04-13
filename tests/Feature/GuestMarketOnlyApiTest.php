<?php

declare(strict_types=1);

namespace Tests\Feature;

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

    public function test_guest_gets_unauthorized_on_cases_index(): void
    {
        $response = $this->getJson('/api/v1/cases');

        $response->assertUnauthorized();
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
