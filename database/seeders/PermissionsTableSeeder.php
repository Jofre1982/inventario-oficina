<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Crea o actualiza todos los permisos y los roles por defecto.
 * Se puede ejecutar varias veces sin duplicar nada:
 *
 *   php artisan db:seed --class=PermissionsTableSeeder
 */
class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $crud = [
            'items' => ['elementos', 'elemento'],
            'categories' => ['categorías', 'categoría'],
            'dependencies' => ['dependencias', 'dependencia'],
            'responsibles' => ['responsables', 'responsable'],
            'providers' => ['proveedores', 'proveedor'],
            'users' => ['usuarios', 'usuario'],
            'roles' => ['roles', 'rol'],
        ];

        foreach ($crud as $prefix => [$plural, $singular]) {
            $this->permission("{$prefix}.index", "Navegar {$plural}", "Lista y busca {$plural}");
            $this->permission("{$prefix}.show", "Ver detalle de {$singular}", "Ver el detalle de cada {$singular}");
            $this->permission("{$prefix}.create", "Crear {$plural}", "Registrar {$plural} nuevos");
            $this->permission("{$prefix}.edit", "Editar {$plural}", "Modificar los datos de {$plural}");
            $this->permission("{$prefix}.destroy", "Eliminar {$plural}", "Eliminar {$plural}");
        }

        $this->permission('change.status.items', 'Activar / desactivar elementos', 'Un elemento inactivo no aparece para entregar');
        $this->permission('items.adjust', 'Dar de baja y marcar reparados', 'Dar de baja unidades o pasar unidades dañadas a disponibles');

        $this->permission('entries.index', 'Navegar entradas', 'Lista y busca entradas');
        $this->permission('entries.show', 'Ver detalle de entrada', 'Ver el detalle de cada entrada');
        $this->permission('entries.create', 'Registrar entradas', 'Registrar entradas nuevas (suma al stock)');
        $this->permission('entries.pdf', 'PDF de entrada', 'Ver el comprobante de entrada en PDF');
        $this->permission('change.status.entries', 'Anular entradas', 'Anular entradas (descuenta del stock)');

        $this->permission('deliveries.index', 'Navegar salidas', 'Lista y busca salidas y préstamos');
        $this->permission('deliveries.show', 'Ver detalle de salida', 'Ver el detalle de cada salida');
        $this->permission('deliveries.create', 'Registrar salidas y préstamos', 'Entregar elementos (resta del stock)');
        $this->permission('deliveries.pdf', 'Acta de entrega', 'Ver e imprimir el acta de entrega en PDF');
        $this->permission('change.status.deliveries', 'Anular salidas', 'Anular salidas sin devoluciones (devuelve al stock)');

        $this->permission('returns.index', 'Navegar devoluciones', 'Lista y busca devoluciones');
        $this->permission('returns.show', 'Ver detalle de devolución', 'Ver el detalle de cada devolución');
        $this->permission('returns.create', 'Registrar devoluciones', 'Recibir elementos devolutivos');
        $this->permission('returns.pdf', 'Acta de devolución', 'Ver e imprimir el acta de devolución en PDF');

        $this->permission('loans.index', 'Ver préstamos y vencidos', 'Préstamos pendientes y vencidos');

        $this->permission('reports.movements', 'Reportes de movimientos', 'Por fechas, por responsable y por dependencia');
        $this->permission('reports.loans', 'Reporte de prestado por persona', 'Qué tiene prestado cada persona');

        $this->permission('organization.index', 'Ver datos de la entidad', 'Ver nombre, NIT, logo, firmas...');
        $this->permission('organization.edit', 'Editar datos de la entidad', 'Modificar los datos que salen en las actas');

        $this->defaultRoles();
    }

    private function permission(string $slug, string $name, string $description): void
    {
        Permission::updateOrCreate(['slug' => $slug], compact('name', 'description'));
    }

    private function defaultRoles(): void
    {
        Role::firstOrCreate(['slug' => 'admin'], [
            'name' => 'Administrador',
            'description' => 'Acceso total al sistema',
            'special' => Role::ALL_ACCESS,
        ]);

        $keeper = Role::firstOrCreate(['slug' => 'almacenista'], [
            'name' => 'Almacenista',
            'description' => 'Registra elementos, entradas, salidas, préstamos y devoluciones; ve reportes',
        ]);
        if ($keeper->wasRecentlyCreated) {
            $keeper->permissions()->sync(Permission::where(fn ($q) => $q
                ->where('slug', 'not like', 'users.%')
                ->where('slug', 'not like', 'roles.%')
                ->where('slug', '!=', 'organization.edit'))
                ->pluck('id'));
        }

        $viewer = Role::firstOrCreate(['slug' => 'consulta'], [
            'name' => 'Consulta',
            'description' => 'Solo consulta elementos, préstamos y reportes',
        ]);
        if ($viewer->wasRecentlyCreated) {
            $viewer->permissions()->sync(Permission::whereIn('slug', [
                'items.index', 'items.show', 'dependencies.index', 'dependencies.show',
                'responsibles.index', 'responsibles.show', 'deliveries.index', 'deliveries.show', 'deliveries.pdf',
                'returns.index', 'returns.show', 'loans.index', 'reports.movements', 'reports.loans',
            ])->pluck('id'));
        }
    }
}
