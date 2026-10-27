<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Dependency;
use App\Models\Item;
use App\Models\ItemReturnDetail;
use App\Models\Provider;
use App\Models\Responsible;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Datos de ejemplo para probar el sistema: dependencias, funcionarios, elementos,
 * entradas, salidas, préstamos (algunos vencidos) y devoluciones del último mes.
 *
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Item::exists()) {
            $this->command?->warn('Ya hay elementos registrados; no se cargan los datos de ejemplo.');

            return;
        }

        $inventory = app(InventoryService::class);
        $user = User::orderBy('id')->firstOrFail();
        $day = fn (int $daysAgo) => Carbon::today()->subDays($daysAgo)->toDateString();

        $categories = collect([
            'Papelería' => 'Papel, carpetas, bolígrafos y útiles de oficina',
            'Aseo y cafetería' => 'Productos de limpieza y cafetería',
            'Tóner y tintas' => 'Cartuchos para impresoras',
            'Equipos de cómputo' => 'Portátiles, proyectores y accesorios',
            'Herramientas' => 'Herramientas de mantenimiento',
            'Audiovisuales' => 'Cámaras, sonido y comunicaciones',
        ])->map(fn ($description, $name) => Category::firstOrCreate(['name' => $name], ['description' => $description]));

        $dependencies = collect([
            'Despacho del Alcalde' => 'Alcalde Municipal',
            'Secretaría de Gobierno' => 'Secretario de Gobierno',
            'Secretaría de Hacienda - Tesorería' => 'Tesorero Municipal',
            'Secretaría de Planeación' => 'Secretaria de Planeación',
            'Secretaría de Salud' => 'Secretario de Salud',
            'Secretaría de Educación y Cultura' => 'Secretaria de Educación',
            'Servicios Generales' => 'Jefe de Servicios Generales',
        ])->map(fn ($head, $name) => Dependency::firstOrCreate(['name' => $name], ['head_name' => $head, 'active' => true]));

        $people = [
            ['1098456123', 'María Fernanda Rojas', 'Secretaria ejecutiva', 'Despacho del Alcalde'],
            ['80234567', 'Carlos Andrés Pérez', 'Secretario de Gobierno', 'Secretaría de Gobierno'],
            ['52789345', 'Luz Marina Gómez', 'Auxiliar administrativa', 'Secretaría de Gobierno'],
            ['1023987654', 'Jorge Iván Martínez', 'Tesorero', 'Secretaría de Hacienda - Tesorería'],
            ['39567812', 'Ana Lucía Torres', 'Técnica de presupuesto', 'Secretaría de Hacienda - Tesorería'],
            ['79345678', 'Pedro Pablo Castro', 'Arquitecto contratista', 'Secretaría de Planeación'],
            ['1110456789', 'Diana Carolina Ruiz', 'Enfermera jefe', 'Secretaría de Salud'],
            ['1065789432', 'Andrés Felipe Mora', 'Coordinador de cultura', 'Secretaría de Educación y Cultura'],
            ['91234765', 'José Antonio Díaz', 'Operario de mantenimiento', 'Servicios Generales'],
            ['63456781', 'Gloria Patricia Vargas', 'Auxiliar de servicios generales', 'Servicios Generales'],
        ];
        $responsibles = collect($people)->mapWithKeys(fn ($p) => [$p[1] => Responsible::firstOrCreate(['document' => $p[0]], [
            'name' => $p[1], 'position' => $p[2], 'dependency_id' => $dependencies[$p[3]]->id, 'active' => true,
        ])]);

        $papeleria = Provider::firstOrCreate(['name' => 'Papelería y Suministros del Centro'], ['nit' => '900456123-1', 'phone' => '607 6345678']);
        $tecnologia = Provider::firstOrCreate(['name' => 'Tecnología Integral S.A.S.'], ['nit' => '901234567-8', 'phone' => '601 7456789']);

        // [código, nombre, tipo, categoría, unidad, mínimo, serial/placa]
        $catalog = [
            ['7702011010101', 'Resma papel carta 75 g', Item::CONSUMABLE, 'Papelería', 'Resma', 20, null],
            ['7702011010202', 'Resma papel oficio 75 g', Item::CONSUMABLE, 'Papelería', 'Resma', 10, null],
            ['7701234500017', 'Carpeta AZ tamaño oficio', Item::CONSUMABLE, 'Papelería', 'Unidad', 10, null],
            ['7701234500024', 'Bolígrafo negro (caja x 12)', Item::CONSUMABLE, 'Papelería', 'Caja', 5, null],
            ['0882780990105', 'Tóner HP 85A negro', Item::CONSUMABLE, 'Tóner y tintas', 'Unidad', 3, null],
            ['0882780990112', 'Tóner HP 105A negro', Item::CONSUMABLE, 'Tóner y tintas', 'Unidad', 2, null],
            ['7702191110018', 'Jabón líquido para manos', Item::CONSUMABLE, 'Aseo y cafetería', 'Galón', 4, null],
            ['7702191110025', 'Papel higiénico institucional', Item::CONSUMABLE, 'Aseo y cafetería', 'Paquete', 6, null],
            ['7702191110032', 'Café molido 500 g', Item::CONSUMABLE, 'Aseo y cafetería', 'Paquete', 3, null],
            ['PL-0001', 'Portátil Lenovo ThinkPad E14', Item::RETURNABLE, 'Equipos de cómputo', 'Unidad', 0, 'Placa 0001 / S/N PF3K9X21'],
            ['PL-0002', 'Portátil HP ProBook 440', Item::RETURNABLE, 'Equipos de cómputo', 'Unidad', 0, 'Placas 0002 a 0004'],
            ['PL-0010', 'Video beam Epson PowerLite', Item::RETURNABLE, 'Equipos de cómputo', 'Unidad', 0, 'Placa 0010'],
            ['PL-0020', 'Taladro percutor Bosch 650 W', Item::RETURNABLE, 'Herramientas', 'Unidad', 0, 'Placa 0020'],
            ['PL-0021', 'Escalera de aluminio 6 pasos', Item::RETURNABLE, 'Herramientas', 'Unidad', 0, null],
            ['PL-0022', 'Extensión eléctrica 10 m', Item::RETURNABLE, 'Herramientas', 'Unidad', 0, null],
            ['PL-0030', 'Cámara fotográfica Canon EOS', Item::RETURNABLE, 'Audiovisuales', 'Unidad', 0, 'Placa 0030'],
            ['PL-0031', 'Radio de comunicación Motorola', Item::RETURNABLE, 'Audiovisuales', 'Unidad', 0, null],
        ];

        $items = collect($catalog)->mapWithKeys(fn ($c) => [$c[1] => Item::create([
            'code' => $c[0], 'name' => $c[1], 'type' => $c[2], 'category_id' => $categories[$c[3]]->id,
            'unit' => $c[4], 'min_stock' => $c[5], 'serial' => $c[6], 'location' => $c[2] === Item::RETURNABLE ? 'Almacén - estante B' : 'Almacén - estante A',
            'stock' => 0, 'unavailable_stock' => 0, 'active' => true,
        ])]);
        $id = fn (string $name) => $items[$name]->id;
        $lines = fn (array $pairs) => collect($pairs)->map(fn ($qty, $name) => ['item_id' => $id($name), 'quantity' => $qty])->values()->all();

        // Entradas
        $inventory->registerEntry(['date' => $day(35), 'provider_id' => $papeleria->id, 'document' => 'Factura FE-1520', 'notes' => 'Contrato de suministro de papelería 2026'], $user->id, $lines([
            'Resma papel carta 75 g' => 60, 'Resma papel oficio 75 g' => 20, 'Carpeta AZ tamaño oficio' => 40,
            'Bolígrafo negro (caja x 12)' => 15, 'Jabón líquido para manos' => 12, 'Papel higiénico institucional' => 20, 'Café molido 500 g' => 10,
        ]));
        $inventory->registerEntry(['date' => $day(34), 'provider_id' => $tecnologia->id, 'document' => 'Contrato 045 de 2026', 'notes' => 'Equipos y tóner'], $user->id, $lines([
            'Tóner HP 85A negro' => 8, 'Tóner HP 105A negro' => 5, 'Portátil Lenovo ThinkPad E14' => 1, 'Portátil HP ProBook 440' => 3,
            'Video beam Epson PowerLite' => 2, 'Cámara fotográfica Canon EOS' => 1, 'Radio de comunicación Motorola' => 4,
        ]));
        $inventory->registerEntry(['date' => $day(33), 'document' => 'Inventario inicial', 'notes' => 'Herramientas que ya estaban en el almacén'], $user->id, $lines([
            'Taladro percutor Bosch 650 W' => 2, 'Escalera de aluminio 6 pasos' => 2, 'Extensión eléctrica 10 m' => 5,
        ]));

        $deliver = fn (string $person, int $daysAgo, ?int $dueInDays, array $pairs, ?string $notes = null) => $inventory->registerDelivery([
            'date' => $day($daysAgo),
            'due_date' => $dueInDays === null ? null : Carbon::today()->subDays($daysAgo)->addDays($dueInDays)->toDateString(),
            'responsible_id' => $responsibles[$person]->id,
            'dependency_id' => $responsibles[$person]->dependency_id,
            'notes' => $notes,
        ], $user->id, $lines($pairs));

        // Movimientos del último mes, en orden de fecha (como se registrarían en la vida real).
        $deliver('María Fernanda Rojas', 30, null, ['Resma papel carta 75 g' => 10, 'Bolígrafo negro (caja x 12)' => 2, 'Café molido 500 g' => 2]);
        $deliver('Pedro Pablo Castro', 28, 14, ['Portátil HP ProBook 440' => 1, 'Cámara fotográfica Canon EOS' => 1], 'Visitas de obra en veredas');
        $deliver('Luz Marina Gómez', 25, null, ['Resma papel carta 75 g' => 8, 'Carpeta AZ tamaño oficio' => 10, 'Tóner HP 85A negro' => 2]);
        $beam = $deliver('Luz Marina Gómez', 22, 5, ['Video beam Epson PowerLite' => 1], 'Capacitación a juntas de acción comunal');
        $deliver('José Antonio Díaz', 20, 7, ['Taladro percutor Bosch 650 W' => 1, 'Extensión eléctrica 10 m' => 2], 'Mantenimiento del palacio municipal');
        $deliver('Ana Lucía Torres', 18, null, ['Resma papel oficio 75 g' => 6, 'Tóner HP 105A negro' => 2, 'Carpeta AZ tamaño oficio' => 12]);
        $inventory->registerReturn($beam, ['date' => $day(17)], $user->id, [
            ['delivery_detail_id' => $beam->details->first()->id, 'quantity' => 1, 'condition' => ItemReturnDetail::GOOD],
        ]);
        $radios = $deliver('Diana Carolina Ruiz', 16, 10, ['Radio de comunicación Motorola' => 2], 'Jornada de vacunación rural');
        $tools = $deliver('José Antonio Díaz', 15, 3, ['Escalera de aluminio 6 pasos' => 1, 'Extensión eléctrica 10 m' => 1]);
        $deliver('Gloria Patricia Vargas', 12, null, ['Jabón líquido para manos' => 6, 'Papel higiénico institucional' => 12, 'Café molido 500 g' => 4]);
        $details = $tools->details->keyBy('item_id');
        $inventory->registerReturn($tools, ['date' => $day(11), 'notes' => 'La extensión llegó con el cable roto'], $user->id, [
            ['delivery_detail_id' => $details[$id('Escalera de aluminio 6 pasos')]->id, 'quantity' => 1, 'condition' => ItemReturnDetail::GOOD],
            ['delivery_detail_id' => $details[$id('Extensión eléctrica 10 m')]->id, 'quantity' => 1, 'condition' => ItemReturnDetail::DAMAGED, 'notes' => 'Cable pelado cerca del enchufe'],
        ]);
        $deliver('Carlos Andrés Pérez', 10, 60, ['Portátil Lenovo ThinkPad E14' => 1, 'Radio de comunicación Motorola' => 2]);
        $deliver('Diana Carolina Ruiz', 6, null, ['Resma papel carta 75 g' => 15, 'Bolígrafo negro (caja x 12)' => 4]);
        $inventory->registerReturn($radios, ['date' => $day(5)], $user->id, [
            ['delivery_detail_id' => $radios->details->first()->id, 'quantity' => 1, 'condition' => ItemReturnDetail::INCOMPLETE, 'notes' => 'Sin cargador'],
        ]);
        $deliver('Andrés Felipe Mora', 4, 7, ['Video beam Epson PowerLite' => 1, 'Extensión eléctrica 10 m' => 1], 'Festival de la cultura');
        $deliver('Jorge Iván Martínez', 3, 30, ['Portátil HP ProBook 440' => 1]);
        $deliver('María Fernanda Rojas', 2, null, ['Resma papel carta 75 g' => 12, 'Resma papel oficio 75 g' => 5, 'Tóner HP 85A negro' => 3]);

        $this->command?->info('Datos de ejemplo cargados: ' . Item::count() . ' elementos, ' . Delivery::count() . ' salidas.');
    }
}
