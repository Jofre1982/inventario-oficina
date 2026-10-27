<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Dependency;
use App\Models\Responsible;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;

/** Funcionarios o personas que reciben elementos. */
class ResponsibleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permissions('responsibles');
    }

    public function index(Request $request)
    {
        $responsibles = Responsible::with('dependency')
            ->withCount(['deliveries as pending_loans_count' => fn ($q) => $q->withPendingLoans()])
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('document', 'like', "%{$term}%")))
            ->when($request->dependency, fn ($q, $id) => $q->where('dependency_id', $id))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $dependencies = Dependency::orderBy('name')->get();

        return view('responsible.index', compact('responsibles', 'dependencies'));
    }

    public function create(Request $request)
    {
        return view('responsible.create', [
            'responsible' => new Responsible(['active' => true, 'dependency_id' => $request->dependency]),
            'dependencies' => Dependency::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $responsible = Responsible::create($this->validated($request));

        // Desde la pantalla de salida se crea en una ventana emergente sin perder lo escrito.
        if ($request->expectsJson()) {
            return response()->json([
                'id' => $responsible->id,
                'name' => $responsible->name,
                'document' => $responsible->document,
                'position' => $responsible->position,
                'dependency_id' => $responsible->dependency_id,
            ], 201);
        }

        return redirect()->route('responsibles.index')->with('success', 'Responsable creado.');
    }

    public function show(Responsible $responsible)
    {
        $responsible->load('dependency');
        $loans = $responsible->pendingLoans();
        $deliveries = Delivery::with(['dependency', 'details.item'])
            ->where('responsible_id', $responsible->id)
            ->latest('date')->latest('id')
            ->paginate(15);

        return view('responsible.show', compact('responsible', 'loans', 'deliveries'));
    }

    public function edit(Responsible $responsible)
    {
        $dependencies = Dependency::where(fn ($q) => $q->active()->orWhere('id', $responsible->dependency_id))->orderBy('name')->get();

        return view('responsible.edit', compact('responsible', 'dependencies'));
    }

    public function update(Request $request, Responsible $responsible)
    {
        $responsible->update($this->validated($request, $responsible));

        return redirect()->route('responsibles.index')->with('success', 'Responsable actualizado.');
    }

    public function destroy(Responsible $responsible)
    {
        return $this->deleteOrExplain($responsible, 'responsibles.index', 'No se puede eliminar: la persona tiene salidas registradas. Puede marcarla como inactiva.');
    }

    private function validated(Request $request, ?Responsible $responsible = null): array
    {
        $data = $request->validate([
            'document' => ['required', 'string', 'max:30', Rule::unique('responsibles')->ignore($responsible)],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'dependency_id' => ['required', 'integer', 'exists:dependencies,id'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [], [
            'document' => 'cédula', 'name' => 'nombre', 'position' => 'cargo',
            'dependency_id' => 'dependencia', 'phone' => 'teléfono', 'email' => 'correo',
        ]);

        return $data + ['active' => $responsible ? $request->boolean('active') : true];
    }
}
