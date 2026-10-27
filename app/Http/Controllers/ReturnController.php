<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\ItemReturn;
use App\Models\ItemReturnDetail;
use App\Services\InventoryService;
use App\Support\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;

/** Devoluciones de elementos devolutivos, con el estado en que regresan. */
class ReturnController extends Controller implements HasMiddleware
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public static function middleware(): array
    {
        return static::permissions('returns', ['returns.pdf' => 'pdf']);
    }

    public function index(Request $request)
    {
        $returns = ItemReturn::with(['delivery.responsible', 'delivery.dependency', 'details', 'user'])
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('id', ltrim($term, '0') ?: 0)
                ->orWhere('delivery_id', ltrim($term, '0') ?: 0)
                ->orWhereHas('delivery.responsible', fn ($r) => $r->where('name', 'like', "%{$term}%")->orWhere('document', 'like', "%{$term}%"))))
            ->when($request->condition, fn ($q, $c) => $q->whereHas('details', fn ($d) => $d->where('condition', $c)))
            ->latest('date')->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('return.index', compact('returns'));
    }

    public function create(Delivery $delivery)
    {
        $delivery->load(['responsible', 'dependency', 'details.item']);

        if (! $delivery->isValid() || $delivery->pendingQuantity() === 0) {
            return redirect()->route('deliveries.show', $delivery)->with('error', 'Esta salida no tiene elementos pendientes por devolver.');
        }

        return view('return.create', compact('delivery'));
    }

    public function store(Request $request, Delivery $delivery)
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array'],
            'lines.*.delivery_detail_id' => ['required', 'integer'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:0'],
            'lines.*.condition' => ['required', Rule::in(array_keys(ItemReturnDetail::CONDITIONS))],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ], [], [
            'date' => 'fecha de devolución', 'notes' => 'observaciones', 'lines' => 'elementos',
            'lines.*.quantity' => 'cantidad', 'lines.*.condition' => 'estado', 'lines.*.notes' => 'observación',
        ]);

        $return = $this->inventory->registerReturn($delivery, $data, $request->user()->id, array_values($data['lines']));

        return redirect()->route('returns.show', $return)->with('success', "Devolución N.º {$return->number} registrada.");
    }

    public function show(ItemReturn $return)
    {
        $return->load(['delivery.responsible', 'delivery.dependency', 'details.item', 'details.deliveryDetail', 'user']);

        return view('return.show', compact('return'));
    }

    /** Acta de devolución con espacio para firmas. */
    public function pdf(ItemReturn $return)
    {
        $return->load(['delivery.responsible', 'delivery.dependency', 'delivery.details.item', 'details.item', 'user']);

        return Pdf::inline('return.pdf', compact('return'), "Acta_devolucion_{$return->number}.pdf");
    }
}
