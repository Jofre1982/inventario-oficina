<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DeliveryDetail;
use App\Models\Item;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;

class ItemController extends Controller implements HasMiddleware
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public static function middleware(): array
    {
        return static::permissions('items', [
            'change.status.items' => 'change_status',
            'items.adjust' => ['writeOff', 'repair'],
        ]);
    }

    public function index(Request $request)
    {
        $term = trim((string) $request->q);

        // Lector de código de barras en el buscador: si el código es exacto, abre el elemento.
        if ($term !== '' && $request->user()->can('items.show') && ! $request->has('page')) {
            $exact = Item::where('code', $term)->get();
            if ($exact->count() === 1) {
                return redirect()->route('items.show', $exact->first());
            }
        }

        $items = Item::with('category')
            ->withLoaned()
            ->search($term)
            ->ofType($request->type)
            ->when($request->category, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->boolean('low'), fn ($q) => $q->lowStock())
            ->when($request->boolean('unavailable'), fn ($q) => $q->where('unavailable_stock', '>', 0))
            ->when($request->status === 'inactive', fn ($q) => $q->where('active', false), fn ($q) => $request->status === 'all' ? $q : $q->active())
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('item.index', compact('items', 'categories'));
    }

    public function create(Request $request)
    {
        return view('item.create', [
            'item' => new Item(['type' => $request->type === Item::RETURNABLE ? Item::RETURNABLE : Item::CONSUMABLE, 'unit' => 'Unidad', 'min_stock' => 0, 'active' => true]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $initial = (int) $request->input('initial_quantity', 0);

        if ($request->hasFile('picture')) {
            $data['image'] = $this->storeImage($request->file('picture'), 'items', 'elem');
        }
        if (empty($data['code'])) {
            $data['code'] = 'TMP-' . uniqid();
        }

        $item = Item::create($data + ['stock' => 0, 'unavailable_stock' => 0, 'active' => true]);

        // Si no se escribió un código se genera uno interno a partir del número del elemento.
        if (str_starts_with($item->code, 'TMP-')) {
            $item->update(['code' => $this->internalCode($item->id)]);
        }

        if ($initial > 0) {
            $this->inventory->registerEntry([
                'date' => now()->toDateString(),
                'document' => 'Saldo inicial',
                'notes' => 'Cantidad inicial al crear el elemento',
            ], $request->user()->id, [['item_id' => $item->id, 'quantity' => $initial]]);
        }

        return redirect()->route('items.show', $item)->with('success', 'Elemento creado' . ($initial > 0 ? " con {$initial} unidades en el almacén." : '.'));
    }

    public function show(Item $item)
    {
        $item->load('category');

        $loans = DeliveryDetail::pendingLoans()
            ->where('item_id', $item->id)
            ->with(['delivery.responsible', 'delivery.dependency'])
            ->get()
            ->sortBy(fn ($d) => $d->delivery->due_date?->timestamp ?? PHP_INT_MAX);

        $movements = $item->movements()
            ->with(['responsible', 'dependency', 'user'])
            ->latest('id')
            ->paginate(20);

        return view('item.show', compact('item', 'loans', 'movements'));
    }

    public function edit(Item $item)
    {
        return view('item.edit', ['item' => $item, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Item $item)
    {
        $data = $this->validated($request, $item);

        if ($request->hasFile('picture')) {
            $data['image'] = $this->storeImage($request->file('picture'), 'items', 'elem');
        }
        if (empty($data['code'])) {
            $data['code'] = $this->internalCode($item->id);
        }

        // Un elemento con préstamos pendientes no puede dejar de ser devolutivo.
        if ($data['type'] !== $item->type && $item->type === Item::RETURNABLE && $item->loaned_quantity > 0) {
            return back()->withInput()->with('error', 'No se puede cambiar a consumible: hay unidades prestadas sin devolver.');
        }

        $item->update($data);

        return redirect()->route('items.show', $item)->with('success', 'Elemento actualizado.');
    }

    public function destroy(Item $item)
    {
        if ($item->stock > 0 || $item->unavailable_stock > 0) {
            return back()->with('error', 'No se puede eliminar: el elemento tiene existencias. Puede desactivarlo o darlo de baja.');
        }

        return $this->deleteOrExplain($item, 'items.index', 'No se puede eliminar: el elemento tiene movimientos. Puede desactivarlo.');
    }

    public function change_status(Item $item)
    {
        $item->update(['active' => ! $item->active]);

        return back()->with('success', $item->active ? 'Elemento activado.' : 'Elemento desactivado: ya no aparece para entregar.');
    }

    /** Dar de baja unidades (pérdida, daño sin arreglo, obsolescencia...). */
    public function writeOff(Request $request, Item $item)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'from' => ['required', Rule::in(['available', 'unavailable'])],
            'reason' => ['required', 'string', 'max:200'],
        ], [], ['quantity' => 'cantidad', 'from' => 'origen', 'reason' => 'motivo']);

        $this->inventory->writeOff($item, (int) $data['quantity'], $data['from'] === 'unavailable', $data['reason'], $request->user()->id);

        return back()->with('success', "Se dieron de baja {$data['quantity']} unidades.");
    }

    /** Las unidades dañadas o incompletas ya arregladas vuelven a estar disponibles. */
    public function repair(Request $request, Item $item)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:200'],
        ], [], ['quantity' => 'cantidad', 'notes' => 'observación']);

        $this->inventory->markRepaired($item, (int) $data['quantity'], $data['notes'] ?? null, $request->user()->id);

        return back()->with('success', "{$data['quantity']} unidades vuelven a estar disponibles.");
    }

    /**
     * Busca elementos por código de barras (exacto), serial/placa o nombre.
     * Lo usan los lectores de código de barras en entradas y salidas.
     */
    public function lookup(Request $request)
    {
        $user = $request->user();
        abort_unless($user->can('entries.create') || $user->can('deliveries.create') || $user->can('items.index'), 403);

        $term = trim((string) $request->q);
        if ($term === '') {
            return response()->json([]);
        }

        $exact = Item::with('category')->where('code', $term)->get();
        $items = $exact->isNotEmpty()
            ? $exact
            : Item::with('category')->search($term)->orderByDesc('active')->orderBy('name')->limit(12)->get();

        return response()->json($items->map(fn (Item $i) => [
            'id' => $i->id,
            'code' => $i->code,
            'name' => $i->name,
            'type' => $i->type,
            'type_label' => $i->type_label,
            'unit' => $i->unit,
            'serial' => $i->serial,
            'stock' => $i->stock,
            'active' => $i->active,
            'category' => $i->category->name ?? '',
        ])->values());
    }

    private function validated(Request $request, ?Item $item = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:60', 'not_regex:/^TMP-/', Rule::unique('items', 'code')->ignore($item)],
            'type' => ['required', Rule::in(array_keys(Item::TYPES))],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'unit' => ['required', 'string', 'max:30'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'serial' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'picture' => ['nullable', 'image', 'max:4096'],
            'initial_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ], [], [
            'name' => 'nombre', 'code' => 'código', 'type' => 'tipo', 'category_id' => 'categoría', 'unit' => 'unidad de medida',
            'min_stock' => 'stock mínimo', 'serial' => 'serial / placa', 'location' => 'ubicación',
            'description' => 'descripción', 'picture' => 'foto', 'initial_quantity' => 'cantidad inicial',
        ]);

        unset($data['picture'], $data['initial_quantity']);
        $data['min_stock'] = (int) ($data['min_stock'] ?? 0);

        return $data;
    }

    private function internalCode(int $id): string
    {
        $code = 'EL-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);

        // Por si alguien ya escribió ese mismo código a mano en otro elemento.
        return Item::where('code', $code)->where('id', '!=', $id)->exists() ? $code . '-' . $id : $code;
    }
}
