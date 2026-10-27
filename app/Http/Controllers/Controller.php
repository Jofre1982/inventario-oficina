<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;

abstract class Controller
{
    /**
     * Une cada acción de un recurso con su permiso
     * (ver database/seeders/PermissionsTableSeeder.php):
     *
     *   items.index   -> index
     *   items.show    -> show
     *   items.create  -> create, store
     *   items.edit    -> edit, update
     *   items.destroy -> destroy
     *
     * $extra permite agregar permisos propios: ['deliveries.pdf' => 'pdf'].
     *
     * @return Middleware[]
     */
    protected static function permissions(string $prefix, array $extra = []): array
    {
        $map = [
            'index' => ['index'],
            'show' => ['show'],
            'create' => ['create', 'store'],
            'edit' => ['edit', 'update'],
            'destroy' => ['destroy'],
        ];

        $middleware = [];
        foreach ($map as $ability => $methods) {
            $middleware[] = new Middleware("can:{$prefix}.{$ability}", only: $methods);
        }
        foreach ($extra as $slug => $methods) {
            $middleware[] = new Middleware("can:{$slug}", only: (array) $methods);
        }

        return $middleware;
    }

    /**
     * Elimina un registro; si está siendo usado (salidas, entradas, elementos...)
     * la base de datos lo impide y se muestra un mensaje en vez de un error.
     */
    protected function deleteOrExplain(Model $model, string $route, string $inUseMessage)
    {
        try {
            $model->delete();
        } catch (QueryException $e) {
            return redirect()->route($route)->with('error', $inUseMessage);
        }

        return redirect()->route($route)->with('success', 'Registro eliminado.');
    }

    /** Guarda una imagen subida en public/image/{folder} y devuelve el nombre. */
    protected function storeImage($file, string $folder, string $prefix): string
    {
        $name = $prefix . '_' . now()->format('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . strtolower($file->extension() ?: 'jpg');
        $file->move(public_path('image' . ($folder ? '/' . $folder : '')), $name);

        return $name;
    }

    /**
     * Si el formulario tuvo un error, devuelve los elementos que ya se habían agregado
     * para mostrarlos otra vez (pantallas de entrada y salida).
     */
    protected function oldLines(Request $request): array
    {
        $old = collect($request->old('items', []))->filter(fn ($l) => ! empty($l['item_id']));
        if ($old->isEmpty()) {
            return [];
        }

        $items = \App\Models\Item::whereIn('id', $old->pluck('item_id'))->get()->keyBy('id');

        return $old->map(fn ($l) => isset($items[$l['item_id']]) ? [
            'item' => [
                'id' => $items[$l['item_id']]->id,
                'code' => $items[$l['item_id']]->code,
                'name' => $items[$l['item_id']]->name,
                'type' => $items[$l['item_id']]->type,
                'type_label' => $items[$l['item_id']]->type_label,
                'unit' => $items[$l['item_id']]->unit,
                'serial' => $items[$l['item_id']]->serial,
                'stock' => $items[$l['item_id']]->stock,
                'active' => $items[$l['item_id']]->active,
            ],
            'quantity' => (int) ($l['quantity'] ?? 1),
        ] : null)->filter()->values()->all();
    }
}
