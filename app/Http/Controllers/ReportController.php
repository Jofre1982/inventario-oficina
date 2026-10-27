<?php

namespace App\Http\Controllers;

use App\Models\DeliveryDetail;
use App\Models\Dependency;
use App\Models\Item;
use App\Models\Movement;
use App\Models\Responsible;
use App\Support\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Reportes:
 *  - Movimientos por fechas, por responsable y por dependencia (misma consulta con distinto filtro).
 *  - Qué tiene prestado cada persona.
 */
class ReportController extends Controller implements HasMiddleware
{
    public const MODES = [
        'date' => 'Movimientos por fechas',
        'responsible' => 'Movimientos por responsable',
        'dependency' => 'Movimientos por dependencia',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('can:reports.movements', only: ['movements']),
            new Middleware('can:reports.loans', only: ['loans']),
        ];
    }

    public function movements(Request $request, string $mode = 'date')
    {
        abort_unless(array_key_exists($mode, self::MODES), 404);

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', Rule::in(array_keys(Movement::TYPES))],
            'responsible' => ['nullable', 'integer', 'exists:responsibles,id'],
            'dependency' => ['nullable', 'integer', 'exists:dependencies,id'],
            'item' => ['nullable', 'integer', 'exists:items,id'],
            'item_type' => ['nullable', Rule::in(array_keys(Item::TYPES))],
        ], [], ['from' => 'fecha inicial', 'to' => 'fecha final']);

        $from = ! empty($filters['from']) ? Carbon::parse($filters['from']) : Carbon::today()->startOfMonth();
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to']) : Carbon::today();

        $responsible = ! empty($filters['responsible']) ? Responsible::with('dependency')->find($filters['responsible']) : null;
        $dependency = ! empty($filters['dependency']) ? Dependency::find($filters['dependency']) : null;
        $item = ! empty($filters['item']) ? Item::find($filters['item']) : null;

        // En "por responsable" y "por dependencia" primero hay que elegir a quién.
        $needsSelection = ($mode === 'responsible' && ! $responsible) || ($mode === 'dependency' && ! $dependency);

        $query = Movement::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($responsible, fn ($q) => $q->where('responsible_id', $responsible->id))
            ->when($dependency, fn ($q) => $q->where('dependency_id', $dependency->id))
            ->when($item, fn ($q) => $q->where('item_id', $item->id))
            ->when($filters['item_type'] ?? null, fn ($q, $t) => $q->whereHas('item', fn ($i) => $i->where('type', $t)));

        // Resumen por elemento: cuánto entró, salió, se prestó y se devolvió.
        $summary = (clone $query)
            ->selectRaw('item_id, type, SUM(quantity) as total')
            ->groupBy('item_id', 'type')
            ->get()
            ->groupBy('item_id')
            ->map(fn ($rows) => $rows->pluck('total', 'type')->map(fn ($v) => (int) $v));
        $summaryItems = Item::whereIn('id', $summary->keys())->orderBy('name')->get();

        $totals = (clone $query)->selectRaw('type, SUM(quantity) as total, COUNT(*) as records')->groupBy('type')->get()->keyBy('type');

        $data = [
            'mode' => $mode,
            'title' => self::MODES[$mode],
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'responsible' => $responsible,
            'dependency' => $dependency,
            'item' => $item,
            'needsSelection' => $needsSelection,
            'summary' => $summary,
            'summaryItems' => $summaryItems,
            'totals' => $totals,
        ];

        $ordered = (clone $query)->with(['item', 'responsible', 'dependency', 'user'])->orderBy('date')->orderBy('id');

        if ($request->boolean('pdf') && ! $needsSelection) {
            return Pdf::inline('report.movements_pdf', $data + ['movements' => $ordered->limit(5000)->get()],
                'Reporte_movimientos_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.pdf', 'landscape');
        }

        return view('report.movements', $data + [
            'movements' => $needsSelection ? collect() : $ordered->paginate(50)->withQueryString(),
            'responsibles' => Responsible::orderBy('name')->get(['id', 'name', 'document']),
            'dependencies' => Dependency::orderBy('name')->get(['id', 'name']),
            'items' => Item::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    /** Qué tiene prestado cada persona (préstamos pendientes agrupados por responsable). */
    public function loans(Request $request)
    {
        $request->validate(['dependency' => ['nullable', 'integer', 'exists:dependencies,id']]);
        $today = Carbon::today();

        $lines = DeliveryDetail::pendingLoans()
            ->select('delivery_details.*')
            ->join('deliveries', 'deliveries.id', '=', 'delivery_details.delivery_id')
            ->with(['item', 'delivery.responsible.dependency', 'delivery.dependency'])
            ->when($request->dependency, fn ($q, $id) => $q->where('deliveries.dependency_id', $id))
            ->when($request->boolean('overdue'), fn ($q) => $q->whereDate('deliveries.due_date', '<', $today))
            ->when($request->q, fn ($q, $term) => $q->whereHas('delivery.responsible', fn ($r) => $r
                ->where('name', 'like', "%{$term}%")->orWhere('document', 'like', "%{$term}%")))
            ->orderBy('deliveries.due_date')
            ->get();

        $people = $lines->groupBy(fn ($l) => $l->delivery->responsible_id)
            ->map(fn ($rows) => [
                'responsible' => $rows->first()->delivery->responsible,
                'lines' => $rows,
                'units' => $rows->sum(fn ($l) => $l->pending),
                'overdue' => $rows->filter(fn ($l) => $l->delivery->due_date && $l->delivery->due_date->lt($today))->count(),
            ])
            ->sortBy(fn ($p) => $p['responsible']->name)
            ->values();

        $data = [
            'people' => $people,
            'today' => $today,
            'dependency' => $request->dependency ? Dependency::find($request->dependency) : null,
            'overdueOnly' => $request->boolean('overdue'),
        ];

        if ($request->boolean('pdf')) {
            return Pdf::inline('report.loans_pdf', $data, 'Prestamos_por_persona_' . $today->format('Ymd') . '.pdf');
        }

        return view('report.loans', $data + ['dependencies' => Dependency::orderBy('name')->get()]);
    }
}
