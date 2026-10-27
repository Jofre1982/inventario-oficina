<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DependencyFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Secretaría ' . $this->faker->unique()->numberBetween(1, 999999), 'active' => true];
    }
}
