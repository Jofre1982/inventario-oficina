<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Dependency;
use App\Models\Item;
use App\Models\Responsible;
use App\Services\InventoryService;
use App\Support\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Carbon;

/** Salidas de consumibles y préstamos de devolutivos, con su acta de entrega. */
class DeliveryController extends Controller implements HasMiddleware
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public static function middleware(): array
    {
        return static::permissions('deliveries', [
            'deliveries.pdf' => 'pdf',
            'change.status.deliveries' => 'cancel',
        ]);
    }

    public function index(Request $request)
    {
        $deliveries = Delivery::with(['responsible', 'dependency', 'details'])
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('id', ltrim($term, '0') ?: 0)
                ->orWhereHas('responsible', fn ($r) => $r->where('name', 'like', "%{$term}%")->orWhere('document', 'like', "%{$term}%"))
                ->orWhereHas('details.item', fn ($i) => $i->where('name', 'like', "%{$term}%")->orWhere('code', $term))))
            ->when($request->dependency, fn ($q, $id) => $q->where('dependency_id', $id))
            ->when($request->responsible, fn ($q, $id) => $q->where('responsible_id', $id))
            ->when($request->status === 'pending', fn ($q) => $q->withPendingLoans())
            ->when($request->status === 'overdue', fn ($q) => $q->overdue())
            ->when($request->status === 'canceled', fn ($q) => $q->where('status', Delivery::CANCELED))
            ->latest('date')->latest('id')
            ->paginate(25)
            ->withQueryString();

        $dependencies = Dependency::orderBy('name')->get();

        return view('delivery.index', compact('deliveries', 'dependencies'));
    }

    public function create(Request $request)
    {
        $responsibles = Responsible::with('dependency')->active()->orderBy('name')->get();
        $dependencies = Dependency::active()->orderBy('name')->get();

        // Si hubo un error, se vuelven a mostrar los elementos que ya se habían agregado.
        $oldLines = $this->oldLines($request);

        return view('delivery.create', [
            'responsibles' => $responsibles,
            'dependencies' => $dependencies,
            'selectedResponsible' => old('responsible_id', $request->responsible),
            'oldLines' => $oldLines,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'responsible_id' => ['required', 'integer', 'exists:responsibles,id'],
            'dependency_id' => ['required', 'integer', 'exists:dependencies,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'due_date.after_or_equal' => 'La fecha de devolución no puede ser anterior a la fecha de salida.',
        ], [
            'responsible_id' => 'responsable', 'dependency_id' => 'dependencia', 'date' => 'fecha de salida',
            'due_date' => 'fecha de devolución', 'notes' => 'observaciones',
            'items' => 'elementos', 'items.*.item_id' => 'elemento', 'items.*.quantity' => 'cantidad',
        ]);

        $delivery = $this->inventory->registerDelivery($data, $request->user()->id, array_values($data['items']));

        return redirect()->route('deliveries.show', $delivery)
            ->with('success', "Salida N.º {$delivery->number} registrada.")
            ->with('open_acta', true);
    }

    public function show(Delivery $delivery)
    {
        $delivery->load(['responsible', 'dependency', 'user', 'details.item.category', 'returns.details.item', 'returns.user']);

        return view('delivery.show', compact('delivery'));
    }

    /** Acta de entrega con espacio para firmas. */
    public function pdf(Delivery $delivery)
    {
        $delivery->load(['responsible', 'dependency', 'user', 'details.item']);

        return Pdf::inline('delivery.pdf', compact('delivery'), "Acta_entrega_{$delivery->number}.pdf");
    }

    public function cancel(Request $request, Delivery $delivery)
    {
        $this->inventory->cancelDelivery($delivery, $request->user()->id);

        return back()->with('success', "Salida N.º {$delivery->number} anulada; los elementos volvieron al almacén.");
    }

    /** Días por defecto para la devolución de devolutivos. */
    public static function defaultDueDate(): string
    {
        return Carbon::today()->addDays(30)->toDateString();
    }
}
