<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Provider;
use App\Services\InventoryService;
use App\Support\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

/** Entradas de elementos al almacén. */
class EntryController extends Controller implements HasMiddleware
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public static function middleware(): array
    {
        return static::permissions('entries', [
            'entries.pdf' => 'pdf',
            'change.status.entries' => 'cancel',
        ]);
    }

    public function index(Request $request)
    {
        $entries = Entry::with(['provider', 'user'])
            ->withSum('details as units', 'quantity')
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('id', ltrim($term, '0') ?: 0)
                ->orWhere('document', 'like', "%{$term}%")
                ->orWhereHas('provider', fn ($p) => $p->where('name', 'like', "%{$term}%"))
                ->orWhereHas('details.item', fn ($i) => $i->where('name', 'like', "%{$term}%")->orWhere('code', $term))))
            ->when($request->provider, fn ($q, $id) => $q->where('provider_id', $id))
            ->latest('date')->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('entry.index', compact('entries'));
    }

    public function create(Request $request)
    {
        return view('entry.create', [
            'providers' => Provider::orderBy('name')->get(),
            'oldLines' => $this->oldLines($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'document' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [], [
            'date' => 'fecha', 'provider_id' => 'proveedor', 'document' => 'documento soporte', 'notes' => 'observaciones',
            'items' => 'elementos', 'items.*.item_id' => 'elemento', 'items.*.quantity' => 'cantidad',
        ]);

        $entry = $this->inventory->registerEntry($data, $request->user()->id, array_values($data['items']));

        return redirect()->route('entries.show', $entry)->with('success', "Entrada N.º {$entry->number} registrada; el stock fue actualizado.");
    }

    public function show(Entry $entry)
    {
        $entry->load(['provider', 'user', 'details.item.category']);

        return view('entry.show', compact('entry'));
    }

    public function pdf(Entry $entry)
    {
        $entry->load(['provider', 'user', 'details.item.category']);

        return Pdf::inline('entry.pdf', compact('entry'), "Entrada_{$entry->number}.pdf");
    }

    public function cancel(Request $request, Entry $entry)
    {
        $this->inventory->cancelEntry($entry, $request->user()->id);

        return back()->with('success', "Entrada N.º {$entry->number} anulada; se descontó del stock.");
    }
}
