<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InstallmentsReportController extends Controller
{
    public function index(Request $request)
    {
        // Get selected month or default to current month
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $selectedDate = Carbon::parse($selectedMonth . '-01');

        // Get filter status (all, paid, unpaid)
        $status = $request->input('status', 'all');

        // Calculate month range
        $monthStart = $selectedDate->copy()->startOfMonth();
        $monthEnd = $selectedDate->copy()->endOfMonth();

        // Get all installments for the selected month with their relationships
        $query = Installment::with([
                'booking.customer',
                'booking.unit.floor.project',
                'payments'
            ])
            ->whereBetween('due_date', [$monthStart, $monthEnd]);

        // Apply status filter
        if ($status === 'paid') {
            $query->where('status', 'paid');
        } elseif ($status === 'unpaid') {
            $query->whereIn('status', ['unpaid', 'partial']);
        }

        $installments = $query->orderBy('due_date')
            ->orderBy('id')
            ->get();

        // Calculate paid amount for each installment and aggregate totals scoped to the table data
        $installments->each(function ($installment) {
            $installment->paid_amount = $installment->payments->sum('amount');
        });

        $totalPaid = $installments->sum('paid_amount');
        $totalInstallmentAmount = $installments->sum('total_amount');
        $totalExpected = max(0, $totalInstallmentAmount - $totalPaid);
        $grandTotal = $totalInstallmentAmount;

        return view('reports.installments', compact(
            'installments',
            'selectedMonth',
            'status',
            'totalPaid',
            'totalExpected',
            'grandTotal'
        ));
    }
}
