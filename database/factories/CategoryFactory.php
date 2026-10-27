<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Categoría ' . $this->faker->unique()->numberBetween(1, 999999)];
    }
}
