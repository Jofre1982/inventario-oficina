<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => $this->faker->company(), 'nit' => (string) $this->faker->numberBetween(800000000, 999999999)];
    }
}
