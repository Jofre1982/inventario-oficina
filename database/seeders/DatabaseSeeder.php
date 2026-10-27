<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos mínimos para empezar. Se puede ejecutar varias veces:
     *   php artisan db:seed
     *
     * Para cargar dependencias, funcionarios, elementos y movimientos de ejemplo:
     *   php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $this->call([
            PermissionsTableSeeder::class, // crea también los roles Administrador, Almacenista y Consulta
            UsersTableSeeder::class,
            OrganizationSeeder::class,
        ]);
    }
}
