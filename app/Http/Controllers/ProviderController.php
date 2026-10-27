<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

/** Proveedores u orígenes de las entradas (opcional). */
class ProviderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permissions('providers');
    }

    public function index(Request $request)
    {
        $providers = Provider::withCount('entries')
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('nit', 'like', "%{$term}%")))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('provider.index', compact('providers'));
    }

    public function create()
    {
        return view('provider.create', ['provider' => new Provider()]);
    }

    public function store(Request $request)
    {
        Provider::create($this->validated($request));

        return redirect()->route('providers.index')->with('success', 'Proveedor creado.');
    }

    public function show(Provider $provider)
    {
        return redirect()->route('entries.index', ['provider' => $provider->id]);
    }

    public function edit(Provider $provider)
    {
        return view('provider.edit', compact('provider'));
    }

    public function update(Request $request, Provider $provider)
    {
        $provider->update($this->validated($request));

        return redirect()->route('providers.index')->with('success', 'Proveedor actualizado.');
    }

    public function destroy(Provider $provider)
    {
        return $this->deleteOrExplain($provider, 'providers.index', 'No se puede eliminar: el proveedor tiene entradas registradas.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [], ['name' => 'nombre', 'phone' => 'teléfono', 'email' => 'correo', 'address' => 'dirección']);
    }
}
