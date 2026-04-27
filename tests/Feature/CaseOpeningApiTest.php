<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BalanceType;
use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Models\Balance;
use App\Models\CaseCategory;
use App\Models\CaseFundAdjustment;
use App\Models\CaseItem;
use App\Models\CaseLevel;
use App\Models\CaseOpening;
use App\Models\GameCase;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CaseOpeningApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_case_returns_guaranteed_item_when_fund_is_below_threshold(): void
    {
        $user = User::factory()->create();
        $gameCase = $this->createCaseFixture(price: '100.00');

        Balance::query()->create([
            'user_id' => $user->id,
            'type' => BalanceType::Main,
            'amount' => '500.00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/cases/{$gameCase->id}/open");

        $response->assertCreated();

        $guaranteedItem = CaseItem::query()
            ->where('name', 'Guaranteed Item')
            ->firstOrFail();

        $response->assertJsonPath('data.won_item.id', $guaranteedItem->id);
        $this->assertDatabaseCount('case_openings', 1);
    }

    public function test_second_opening_falls_back_to_guaranteed_when_first_expensive_drop_drains_fund(): void
    {
        $user = User::factory()->create();
        $gameCase = $this->createCaseFixture(price: '100.00');

        Balance::query()->create([
            'user_id' => $user->id,
            'type' => BalanceType::Main,
            'amount' => '500.00',
        ]);

        CaseFundAdjustment::query()->create([
            'case_id' => $gameCase->id,
            'type' => 'manual',
            'amount' => '2000.00',
            'comment' => 'Seed fund for expensive drop',
        ]);

        Sanctum::actingAs($user);

        $first = $this->postJson("/api/v1/cases/{$gameCase->id}/open");
        $second = $this->postJson("/api/v1/cases/{$gameCase->id}/open");

        $expensiveItem = CaseItem::query()->where('name', 'Expensive Item')->firstOrFail();
        $guaranteedItem = CaseItem::query()->where('name', 'Guaranteed Item')->firstOrFail();

        $first->assertCreated()->assertJsonPath('data.won_item.id', $expensiveItem->id);
        $second->assertCreated()->assertJsonPath('data.won_item.id', $guaranteedItem->id);
        $this->assertDatabaseCount('case_openings', 2);
    }

    public function test_withdraw_marks_item_as_withdrawn_and_uses_user_trade_url(): void
    {
        $user = User::factory()->create([
            'trade_url' => 'https://steamcommunity.com/tradeoffer/new/?partner=123&token=abc',
        ]);
        $gameCase = $this->createCaseFixture(price: '100.00');
        $opening = $this->createOpeningForUser($user, $gameCase);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/profile/case-inventory/{$opening->id}/withdraw");

        $response->assertOk()
            ->assertJsonPath('data.id', $opening->id)
            ->assertJsonPath('data.status', 'withdrawn');

        $this->assertDatabaseHas('case_openings', [
            'id' => $opening->id,
            'status' => 'withdrawn',
        ]);
    }

    public function test_withdraw_requires_trade_url_if_user_profile_has_no_trade_url(): void
    {
        $user = User::factory()->create(['trade_url' => null]);
        $gameCase = $this->createCaseFixture(price: '100.00');
        $opening = $this->createOpeningForUser($user, $gameCase);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/profile/case-inventory/{$opening->id}/withdraw");

        $response->assertUnprocessable()
            ->assertJsonPath('errors.trade_url.0', 'Укажите ссылку на обмен Steam в профиле или передайте trade_url в запросе.');
    }

    public function test_open_case_spends_bonus_before_main_balance(): void
    {
        $user = User::factory()->create();
        $gameCase = $this->createCaseFixture(price: '100.00');

        Balance::query()->create([
            'user_id' => $user->id,
            'type' => BalanceType::Main,
            'amount' => '50.00',
        ]);
        Balance::query()->create([
            'user_id' => $user->id,
            'type' => BalanceType::Bonus,
            'amount' => '70.00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/cases/{$gameCase->id}/open");
        $response->assertCreated();

        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'type' => BalanceType::Bonus->value,
            'amount' => '0.00',
        ]);
        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'type' => BalanceType::Main->value,
            'amount' => '20.00',
        ]);

        $txs = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'case_open')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $txs);
        $this->assertSame(BalanceType::Bonus, $txs[0]->balance_type);
        $this->assertSame('-70.00', (string) $txs[0]->amount);
        $this->assertSame(BalanceType::Main, $txs[1]->balance_type);
        $this->assertSame('-30.00', (string) $txs[1]->amount);
    }

    public function test_open_case_with_quantity_returns_multiple_results(): void
    {
        $user = User::factory()->create();
        $gameCase = $this->createCaseFixture(price: '100.00');

        Balance::query()->create([
            'user_id' => $user->id,
            'type' => BalanceType::Main,
            'amount' => '500.00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/cases/{$gameCase->id}/open", [
            'quantity' => 3,
            'fast' => true,
        ]);

        $response->assertCreated()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.quantity', 3)
            ->assertJsonPath('meta.fast', true);

        $this->assertDatabaseCount('case_openings', 3);
    }

    public function test_public_live_feed_returns_recent_openings(): void
    {
        $user = User::factory()->create();
        $gameCase = $this->createCaseFixture(price: '100.00');
        $opening = $this->createOpeningForUser($user, $gameCase);

        $response = $this->getJson('/api/v1/cases/live');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $opening->id)
            ->assertJsonPath('data.0.case.id', $gameCase->id)
            ->assertJsonPath('data.0.user.id', $user->id);
    }

    private function createCaseFixture(string $price): GameCase
    {
        $category = CaseCategory::query()->create([
            'name' => 'Test Category',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $gameCase = GameCase::query()->create([
            'name' => 'Test Case',
            'description' => null,
            'shadow_color' => null,
            'image_url' => null,
            'price' => $price,
            'category_id' => $category->id,
            'sort_order' => 1,
            'is_active' => false,
            'is_featured_on_home' => false,
        ]);

        $expensiveLevel = CaseLevel::query()->create([
            'case_id' => $gameCase->id,
            'level' => 1,
            'name' => 'Expensive',
            'chance' => '100.00',
            'prize_amount' => '1000.00',
        ]);

        $guaranteedLevel = CaseLevel::query()->create([
            'case_id' => $gameCase->id,
            'level' => 5,
            'name' => 'Guaranteed',
            'chance' => '0.00',
            'prize_amount' => '50.00',
        ]);

        CaseItem::query()->create([
            'case_level_id' => $expensiveLevel->id,
            'name' => 'Expensive Item',
            'image_url' => null,
            'price' => '1000.00',
            'wear' => ItemWear::FN,
            'rarity' => ItemRarity::Covert,
        ]);

        CaseItem::query()->create([
            'case_level_id' => $guaranteedLevel->id,
            'name' => 'Guaranteed Item',
            'image_url' => null,
            'price' => '50.00',
            'wear' => ItemWear::FT,
            'rarity' => ItemRarity::IndustrialGrade,
        ]);

        $gameCase->update(['is_active' => true]);

        return $gameCase->fresh();
    }

    private function createOpeningForUser(User $user, GameCase $gameCase): CaseOpening
    {
        $item = CaseItem::query()
            ->whereHas('level', static fn ($q) => $q->where('case_id', $gameCase->id)->where('level', 5))
            ->firstOrFail();

        return CaseOpening::query()->create([
            'user_id' => $user->id,
            'case_id' => $gameCase->id,
            'case_item_id' => $item->id,
            'cost' => '100.00',
            'won_item_price' => (string) $item->price,
            'status' => 'in_inventory',
        ]);
    }
}
