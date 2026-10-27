<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Usuario administrador inicial (el instalador pide cambiar el correo y la contraseña).
     * Solo se crea si todavía no hay usuarios.
     */
    public function run(): void
    {
        if (User::exists()) {
            $admin = User::orderBy('id')->first();
        } else {
            $admin = User::create([
                'name' => 'Administrador',
                'email' => 'admin@inventario.com',
                'password' => Hash::make('secret'),
            ]);
        }

        $role = Role::where('slug', 'admin')->first();
        if ($role && ! $admin->roles()->exists()) {
            $admin->roles()->attach($role->id);
        }
    }
}
