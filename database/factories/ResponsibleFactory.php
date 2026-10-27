<?php

namespace Database\Factories;

use App\Models\Dependency;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResponsibleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document' => (string) $this->faker->unique()->numberBetween(10000000, 1199999999),
            'name' => $this->faker->name(),
            'position' => 'Auxiliar administrativo',
            'dependency_id' => Dependency::factory(),
            'active' => true,
        ];
    }
}
