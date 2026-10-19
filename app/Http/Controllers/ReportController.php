<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();
        if ($from > $to) { [$from, $to] = [$to, $from]; }

        $payments = Payment::query()->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->latest()->get();
        $movements = StockMovement::query()->whereDate('movement_date', '>=', $from)->whereDate('movement_date', '<=', $to)->with(['product:id,name,sku', 'warehouse:id,name'])->latest('movement_date')->get();
        $attendance = Attendance::query()->whereDate('attendance_date', '>=', $from)->whereDate('attendance_date', '<=', $to)->with('person:id,full_name,employee_number')->latest('attendance_date')->get();
        $summary = [
            'payments_count' => $payments->count(),
            'payments_total' => (float) $payments->where('status', 'approved')->sum('amount'),
            'movements_count' => $movements->count(),
            'attendance_count' => $attendance->count(),
            'products_count' => Product::count(),
        ];
        return view('reports.index', compact('from', 'to', 'payments', 'movements', 'attendance', 'summary'));
    }
}
