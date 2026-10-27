<?php

namespace App\Http\Controllers;

use App\Models\DeliveryDetail;
use App\Models\Dependency;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;

/** Préstamos pendientes de devolución y préstamos vencidos. */
class LoanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:loans.index')];
    }

    public function index(Request $request)
    {
        $today = Carbon::today();

        $loans = DeliveryDetail::pendingLoans()
            ->select('delivery_details.*')
            ->join('deliveries', 'deliveries.id', '=', 'delivery_details.delivery_id')
            ->with(['item', 'delivery.responsible', 'delivery.dependency'])
            ->when($request->status === 'overdue' || $request->routeIs('loans.overdue'), fn ($q) => $q->whereDate('deliveries.due_date', '<', $today))
            ->when($request->status === 'on_time', fn ($q) => $q->whereDate('deliveries.due_date', '>=', $today))
            ->when($request->dependency, fn ($q, $id) => $q->where('deliveries.dependency_id', $id))
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w
                ->whereHas('item', fn ($i) => $i->where('name', 'like', "%{$term}%")->orWhere('code', $term)->orWhere('serial', 'like', "%{$term}%"))
                ->orWhereHas('delivery.responsible', fn ($r) => $r->where('name', 'like', "%{$term}%")->orWhere('document', 'like', "%{$term}%"))))
            ->orderBy('deliveries.due_date')
            ->orderBy('deliveries.id')
            ->paginate(50)
            ->withQueryString();

        $overdueOnly = $request->status === 'overdue' || $request->routeIs('loans.overdue');

        return view('loan.index', [
            'loans' => $loans,
            'dependencies' => Dependency::orderBy('name')->get(),
            'overdueOnly' => $overdueOnly,
            'today' => $today,
        ]);
    }
}
