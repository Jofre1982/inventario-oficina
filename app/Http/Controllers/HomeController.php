<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\DeliveryDetail;
use App\Models\Item;
use App\Models\Movement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /** Inicio con los datos reales del almacén. */
    public function index()
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $overdue = DeliveryDetail::pendingLoans()
            ->select('delivery_details.*')
            ->join('deliveries', 'deliveries.id', '=', 'delivery_details.delivery_id')
            ->whereDate('deliveries.due_date', '<', $today)
            ->with(['item', 'delivery.responsible', 'delivery.dependency'])
            ->orderBy('deliveries.due_date')
            ->get();

        $pendingUnits = (int) DeliveryDetail::pendingLoans()->sum(DB::raw('quantity - returned'));

        // Unidades que salen y entran por día en las últimas 2 semanas (incluye días sin movimiento).
        $days = collect(range(13, 0))->map(fn ($i) => $today->copy()->subDays($i));
        $byDay = Movement::query()
            ->where('date', '>=', $days->first())
            ->whereIn('type', [Movement::OUTPUT, Movement::LOAN, Movement::ENTRY, Movement::RETURN])
            ->get(['date', 'type', 'quantity'])
            ->groupBy(fn ($m) => $m->date->toDateString());

        $chart = [
            'labels' => $days->map(fn ($d) => $d->format('d/m'))->values(),
            'out' => $days->map(fn ($d) => (int) collect($byDay[$d->toDateString()] ?? [])->whereIn('type', [Movement::OUTPUT, Movement::LOAN])->sum('quantity'))->values(),
            'in' => $days->map(fn ($d) => (int) collect($byDay[$d->toDateString()] ?? [])->whereIn('type', [Movement::ENTRY, Movement::RETURN])->sum('quantity'))->values(),
        ];

        return view('dashboard', [
            'overdue' => $overdue,
            'overdueResponsibles' => $overdue->pluck('delivery.responsible_id')->unique()->count(),
            'pendingUnits' => $pendingUnits,
            'activeLoans' => Delivery::withPendingLoans()->count(),
            'deliveriesMonth' => Delivery::valid()->where('date', '>=', $monthStart)->count(),
            'unitsOutMonth' => (int) Movement::whereIn('type', [Movement::OUTPUT, Movement::LOAN])->where('date', '>=', $monthStart)->sum('quantity'),
            'itemCount' => Item::active()->count(),
            'consumableCount' => Item::active()->ofType(Item::CONSUMABLE)->count(),
            'returnableCount' => Item::active()->ofType(Item::RETURNABLE)->count(),
            'lowStock' => Item::active()->lowStock()->orderBy('stock')->limit(8)->get(),
            'lowStockCount' => Item::active()->lowStock()->count(),
            'unavailableCount' => Item::where('unavailable_stock', '>', 0)->count(),
            'dueSoon' => DeliveryDetail::pendingLoans()
                ->select('delivery_details.*')
                ->join('deliveries', 'deliveries.id', '=', 'delivery_details.delivery_id')
                ->whereBetween('deliveries.due_date', [$today->toDateString(), $today->copy()->addDays(7)->toDateString()])
                ->with(['item', 'delivery.responsible'])
                ->orderBy('deliveries.due_date')
                ->limit(8)
                ->get(),
            'latestDeliveries' => Delivery::with(['responsible', 'dependency', 'details'])->latest('date')->latest('id')->limit(8)->get(),
            'chart' => $chart,
            'today' => $today,
        ]);
    }
}
