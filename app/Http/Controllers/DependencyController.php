<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\DeliveryDetail;
use App\Models\Dependency;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;

/** Dependencias u oficinas de la entidad. */
class DependencyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permissions('dependencies');
    }

    public function index(Request $request)
    {
        $dependencies = Dependency::withCount('responsibles')
            ->withCount(['deliveries as pending_loans_count' => fn ($q) => $q->withPendingLoans()])
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('dependency.index', compact('dependencies'));
    }

    public function create()
    {
        return view('dependency.create', ['dependency' => new Dependency(['active' => true])]);
    }

    public function store(Request $request)
    {
        Dependency::create($this->validated($request));

        return redirect()->route('dependencies.index')->with('success', 'Dependencia creada.');
    }

    public function show(Dependency $dependency)
    {
        $dependency->load(['responsibles' => fn ($q) => $q->orderBy('name')]);

        $loans = DeliveryDetail::pendingLoans()
            ->whereHas('delivery', fn ($q) => $q->where('dependency_id', $dependency->id))
            ->with(['item', 'delivery.responsible'])
            ->get()
            ->sortBy(fn ($d) => $d->delivery->due_date?->timestamp ?? PHP_INT_MAX);

        $deliveries = Delivery::with(['responsible', 'details'])
            ->where('dependency_id', $dependency->id)
            ->latest('date')->latest('id')
            ->limit(15)
            ->get();

        return view('dependency.show', compact('dependency', 'loans', 'deliveries'));
    }

    public function edit(Dependency $dependency)
    {
        return view('dependency.edit', compact('dependency'));
    }

    public function update(Request $request, Dependency $dependency)
    {
        $dependency->update($this->validated($request, $dependency));

        return redirect()->route('dependencies.index')->with('success', 'Dependencia actualizada.');
    }

    public function destroy(Dependency $dependency)
    {
        return $this->deleteOrExplain($dependency, 'dependencies.index', 'No se puede eliminar: la dependencia tiene funcionarios o salidas. Puede marcarla como inactiva.');
    }

    private function validated(Request $request, ?Dependency $dependency = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('dependencies')->ignore($dependency)],
            'head_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
        ], [], ['name' => 'nombre', 'head_name' => 'jefe de la dependencia', 'phone' => 'teléfono']);

        return $data + ['active' => $request->boolean('active')];
    }
}
