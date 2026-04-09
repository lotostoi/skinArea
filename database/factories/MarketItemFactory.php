<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ItemCategory;
use App\Enums\ItemRarity;
use App\Enums\ItemWear;
use App\Enums\MarketItemStatus;
use App\Models\MarketItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketItem>
 */
class MarketItemFactory extends Factory
{
    protected $model = MarketItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seller_id' => User::factory(),
            'asset_id' => (string) fake()->unique()->numerify('################'),
            'name' => fake()->words(3, true).' | Field-Tested',
            'image_url' => fake()->optional()->url(),
            'wear' => ItemWear::FT,
            'float_value' => fake()->randomFloat(4, 0.15, 0.45),
            'rarity' => ItemRarity::MilSpec,
            'category' => ItemCategory::Rifles,
            'price' => fake()->randomFloat(2, 1, 500),
            'status' => MarketItemStatus::Active,
        ];
    }
}
