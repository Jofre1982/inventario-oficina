<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/** Datos de la alcaldía u oficina (salen en actas y reportes). */
class OrganizationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:organization.index', only: ['index']),
            new Middleware('can:organization.edit', only: ['update']),
        ];
    }

    public function index()
    {
        return view('organization.index', ['organization' => Organization::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'keeper_name' => ['nullable', 'string', 'max:255'],
            'keeper_title' => ['nullable', 'string', 'max:255'],
            'delivery_terms' => ['nullable', 'string', 'max:2000'],
            'picture' => ['nullable', 'image', 'max:4096'],
        ], [], [
            'name' => 'nombre', 'address' => 'dirección', 'phone' => 'teléfono', 'email' => 'correo', 'city' => 'municipio',
            'keeper_name' => 'nombre de quien entrega', 'keeper_title' => 'cargo de quien entrega',
            'delivery_terms' => 'texto del acta', 'picture' => 'logo',
        ]);

        unset($data['picture']);
        if ($request->hasFile('picture')) {
            $data['logo'] = $this->storeImage($request->file('picture'), '', 'logo');
        }

        Organization::current()->update($data);

        return redirect()->route('organization.index')->with('success', 'Datos de la entidad actualizados.');
    }
}
