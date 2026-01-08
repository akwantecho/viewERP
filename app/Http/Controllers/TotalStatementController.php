<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Payment;
use App\Models\Installment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TotalStatementController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $selectedMonth = $request->input('month', $now->format('Y-m'));
        $selectedDate = Carbon::parse($selectedMonth . '-01');

        // Helper to calculate paid/expected for installments due within a month window
        $summarizeMonth = function (Carbon $start, Carbon $end): array {
            $installments = Installment::with('payments')
                ->whereBetween('due_date', [$start, $end])
                ->get();

            $installments->each(function ($installment) {
                $installment->paid_amount = $installment->payments->sum('amount');
            });

            $paid = $installments->sum('paid_amount');
            $total = $installments->sum('total_amount');
            $expected = max(0, $total - $paid);

            return [$paid, $expected];
        };

        // Calculate current month payments and expected (based on installments due this month)
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        [$currentMonthPaid, $currentMonthExpected] = $summarizeMonth($currentMonthStart, $currentMonthEnd);

        // Calculate next month payments and expected
        $nextMonthStart = $now->copy()->addMonth()->startOfMonth();
        $nextMonthEnd = $now->copy()->addMonth()->endOfMonth();
        [$nextMonthPaid, $nextMonthExpected] = $summarizeMonth($nextMonthStart, $nextMonthEnd);

        // Calculate selected month payments and expected
        $selectedMonthStart = $selectedDate->copy()->startOfMonth();
        $selectedMonthEnd = $selectedDate->copy()->endOfMonth();
        [$selectedMonthPaid, $selectedMonthExpected] = $summarizeMonth($selectedMonthStart, $selectedMonthEnd);

        // Get all projects with their bookings and payment data
        $projects = Project::withCount(['floors' => function ($query) {
                $query->has('units');
            }])
            ->with(['units.booking.payments'])
            ->get()
            ->map(function ($project) {
                $bookings = Booking::where('project_id', $project->id)->get();

                // Calculate total sale price for this project
                $totalSalePrice = $bookings->sum('total_price');

                // Calculate total paid amount for this project
                $totalPaid = Payment::whereIn('booking_id', $bookings->pluck('id'))
                    ->sum('amount');

                // Calculate progress percentage
                $progress = $totalSalePrice > 0
                    ? round(($totalPaid / $totalSalePrice) * 100, 1)
                    : 0;

                $project->total_sale_price = $totalSalePrice;
                $project->total_paid = $totalPaid;
                $project->progress = $progress;
                $project->remaining = max(0, $totalSalePrice - $totalPaid);

                return $project;
            })
            ->sortByDesc('total_sale_price');

        return view('reports.total-statement', compact(
            'projects',
            'currentMonthPaid',
            'currentMonthExpected',
            'nextMonthPaid',
            'nextMonthExpected',
            'selectedMonth',
            'selectedMonthPaid',
            'selectedMonthExpected'
        ));
    }
}
