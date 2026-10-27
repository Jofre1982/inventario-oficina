<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => (string) $this->faker->unique()->numberBetween(1000000, 9999999),
            'name' => ucfirst($this->faker->unique()->words(3, true)),
            'type' => Item::CONSUMABLE,
            'category_id' => Category::factory(),
            'unit' => 'Unidad',
            'stock' => 0,
            'unavailable_stock' => 0,
            'min_stock' => 0,
            'active' => true,
        ];
    }

    public function returnable(): static
    {
        return $this->state(['type' => Item::RETURNABLE]);
    }
}
