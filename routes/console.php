<?php

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Asistente de configuración inicial (lo usa instalar.bat)
|--------------------------------------------------------------------------
| Pregunta el nombre de la entidad, quién firma las actas y los datos del
| administrador. Presionar Enter deja el valor actual.
*/
Artisan::command('inventario:configurar', function () {
    $organization = Organization::current();
    $admin = User::whereHas('roles', fn ($q) => $q->where('special', Role::ALL_ACCESS))->orderBy('id')->first()
        ?? User::orderBy('id')->first();

    $this->info('');
    $this->info('  DATOS DE LA ENTIDAD (Enter = dejar como está)');
    $this->info('');

    $name = $this->ask('Nombre de la alcaldía u oficina', $organization->name);
    $nit = $this->ask('NIT (opcional, - para dejar vacío)', $organization->nit ?: '-');
    $city = $this->ask('Municipio (opcional)', $organization->city ?: '-');
    $address = $this->ask('Dirección y teléfono (opcional)', $organization->address ?: '-');
    $keeper = $this->ask('Nombre de quien entrega los elementos y firma las actas (almacenista)', $organization->keeper_name ?: '-');
    $keeperTitle = $this->ask('Cargo de quien entrega', $organization->keeper_title ?: 'Almacenista');

    $clean = fn ($v) => trim((string) $v) === '-' ? null : trim((string) $v);
    $organization->update([
        'name' => trim($name),
        'nit' => $clean($nit),
        'city' => $clean($city),
        'address' => $clean($address),
        'keeper_name' => $clean($keeper),
        'keeper_title' => $clean($keeperTitle),
    ]);

    if (! $admin) {
        $this->warn('No hay usuario administrador; ejecute primero las migraciones con --seed.');

        return 1;
    }

    $this->info('');
    $this->info('  USUARIO ADMINISTRADOR');
    $this->info('');

    $email = $this->ask('Correo para ingresar', $admin->email);
    while (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = $this->ask('Ese correo no es válido. Correo para ingresar', $admin->email);
    }

    $password = null;
    if (Hash::check('secret', $admin->password) || $this->confirm('¿Cambiar la contraseña del administrador?', false)) {
        while (true) {
            $password = (string) $this->secret('Nueva contraseña (mínimo 6 caracteres, no se ve al escribir)');
            if (mb_strlen($password) < 6) {
                $this->error('Muy corta, use al menos 6 caracteres.');
                continue;
            }
            if ($this->secret('Repita la contraseña') !== $password) {
                $this->error('No coinciden, intente de nuevo.');
                continue;
            }
            break;
        }
    }

    $admin->email = trim($email);
    if ($password !== null) {
        $admin->password = Hash::make($password);
    }
    $admin->save();

    $this->info('');
    $this->info("  Listo. Ingrese con: {$admin->email}");

    return 0;
})->purpose('Configura los datos de la entidad y el usuario administrador');
