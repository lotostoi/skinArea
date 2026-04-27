<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\CaseCategory;
use App\Models\CaseLevel;
use App\Models\GameCase;
use App\Services\CaseEconomyValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CaseEconomyValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_case_cannot_be_created_without_levels(): void
    {
        $category = CaseCategory::query()->create([
            'name' => 'Validator',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $this->expectException(ValidationException::class);

        GameCase::query()->create([
            'name' => 'Broken Active Case',
            'description' => null,
            'shadow_color' => null,
            'image_url' => null,
            'price' => '200.00',
            'category_id' => $category->id,
            'sort_order' => 1,
            'is_active' => true,
            'is_featured_on_home' => false,
        ]);
    }

    public function test_validator_rejects_sum_of_chances_not_equal_to_hundred(): void
    {
        $case = $this->createCase('200.00');

        CaseLevel::query()->create([
            'case_id' => $case->id,
            'level' => 1,
            'name' => 'Rare',
            'chance' => '10.00',
            'prize_amount' => '500.00',
        ]);
        CaseLevel::query()->create([
            'case_id' => $case->id,
            'level' => 5,
            'name' => 'Guaranteed',
            'chance' => '85.00',
            'prize_amount' => '100.00',
        ]);

        $this->expectException(ValidationException::class);
        $case->update(['is_active' => true]);
    }

    public function test_validator_rejects_wrong_guaranteed_amount(): void
    {
        $case = $this->createCase('200.00');

        CaseLevel::query()->create([
            'case_id' => $case->id,
            'level' => 1,
            'name' => 'Rare',
            'chance' => '10.00',
            'prize_amount' => '500.00',
        ]);
        CaseLevel::query()->create([
            'case_id' => $case->id,
            'level' => 5,
            'name' => 'Guaranteed',
            'chance' => '90.00',
            'prize_amount' => '70.00',
        ]);

        $this->expectException(ValidationException::class);
        $case->update(['is_active' => true]);
    }

    public function test_validator_accepts_valid_active_case_configuration(): void
    {
        $case = $this->createCase('200.00');

        CaseLevel::query()->create([
            'case_id' => $case->id,
            'level' => 1,
            'name' => 'Rare',
            'chance' => '10.00',
            'prize_amount' => '500.00',
        ]);
        CaseLevel::query()->create([
            'case_id' => $case->id,
            'level' => 5,
            'name' => 'Guaranteed',
            'chance' => '90.00',
            'prize_amount' => '100.00',
        ]);
        $case->update(['is_active' => true]);

        app(CaseEconomyValidator::class)->validate($case, $case->levels()->get());
        $this->assertTrue(true);
    }

    private function createCase(string $price): GameCase
    {
        $category = CaseCategory::query()->create([
            'name' => 'Validator',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        return GameCase::query()->create([
            'name' => 'Validator Case',
            'description' => null,
            'shadow_color' => null,
            'image_url' => null,
            'price' => $price,
            'category_id' => $category->id,
            'sort_order' => 1,
            'is_active' => false,
            'is_featured_on_home' => false,
        ]);
    }
}
