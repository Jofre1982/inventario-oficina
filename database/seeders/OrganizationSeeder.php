<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        if (Organization::exists()) {
            return;
        }

        Organization::create([
            'name' => 'Alcaldía Municipal',
            'keeper_title' => 'Almacenista',
            'delivery_terms' => Organization::DEFAULT_TERMS,
        ]);
    }
}
