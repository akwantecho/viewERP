<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Payment;
use App\Models\Installment;
use App\Models\Booking;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

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

    public function totalStatementAll()
    {
        // Get all projects with their units count
        $projects = Project::withCount(['floors' => function ($query) {
            $query->has('units');
        }])
        ->with('units')
        ->get();

        // Prepare rows for the view
        $rows = $projects->map(function ($project) {
            return [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'project_code' => $project->code,
                'units_count' => $project->units->count(),
            ];
        });

        // Get recent sync logs with pagination
        $logs = SyncLog::with('project')
            ->latest()
            ->paginate(20);

        return view('reports.total_statement_all', compact('rows', 'logs'));
    }

    public function exportTotalStatementAllPdf()
    {
        // Get all projects with their bookings and payment data
        $projects = Project::withCount(['floors' => function ($query) {
            $query->has('units');
        }])
        ->with(['units.booking.payments'])
        ->get()
        ->map(function ($project) {
            $bookings = Booking::where('project_id', $project->id)->get();

            $totalSalePrice = $bookings->sum('total_price');
            $totalPaid = Payment::whereIn('booking_id', $bookings->pluck('id'))
                ->sum('amount');

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

        $pdf = Pdf::loadView('reports.total-statement-all-pdf', compact('projects'));

        return $pdf->download('total-statement-all-projects-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportStatementPdf(Project $project)
    {
        // Eager-load units with floor and booking data (same as statement view)
        $units = $project->units()->with(['floor', 'booking.installments', 'booking.payments'])->get();

        $pdf = Pdf::loadView('reports.project-statement-pdf', compact('project', 'units'));

        return $pdf->download($project->code . '-statement-' . now()->format('Y-m-d') . '.pdf');
    }

    public function syncProjectStatement(Project $project)
    {
        try {
            // Load units for the PDF view
            $units = $project->units()->with(['floor', 'booking.installments', 'booking.payments'])->get();

            // Generate PDF
            $pdf = Pdf::loadView('reports.project-statement-pdf', compact('project', 'units'));
            $pdfContent = $pdf->output();

            // Upload to S3
            $fileName = 'statements/' . $project->code . '-statement-' . now()->format('Y-m-d') . '.pdf';
            Storage::disk('s3')->put($fileName, $pdfContent);

            // Log success
            SyncLog::create([
                'project_id' => $project->id,
                'batch_id' => uniqid('single_'),
                'local_path' => null,
                'remote_path' => $fileName,
                'status' => 'success',
                'trigger' => 'manual',
                'message' => 'Project statement backed up successfully',
                'triggered_by' => auth()->user()->name ?? 'system',
                'file_hash' => md5($pdfContent),
            ]);

            return redirect()
                ->route('projects.statement', $project)
                ->with('success', 'Statement backed up to S3 successfully.');

        } catch (\Exception $e) {
            // Log failure
            SyncLog::create([
                'project_id' => $project->id,
                'batch_id' => uniqid('single_'),
                'local_path' => null,
                'remote_path' => null,
                'status' => 'failed',
                'trigger' => 'manual',
                'message' => 'Failed to backup: ' . $e->getMessage(),
                'triggered_by' => auth()->user()->name ?? 'system',
                'error_trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('projects.statement', $project)
                ->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function syncAllProjects()
    {
        try {
            $projects = Project::all();
            $successCount = 0;
            $failCount = 0;
            $batchId = uniqid('batch_');

            foreach ($projects as $project) {
                try {
                    // Load units for the PDF view
                    $units = $project->units()->with(['floor', 'booking.installments', 'booking.payments'])->get();

                    // Generate PDF
                    $pdf = Pdf::loadView('reports.project-statement-pdf', compact('project', 'units'));
                    $pdfContent = $pdf->output();

                    // Upload to S3
                    $fileName = 'statements/' . $project->code . '-statement-' . now()->format('Y-m-d') . '.pdf';
                    Storage::disk('s3')->put($fileName, $pdfContent);

                    // Log success
                    SyncLog::create([
                        'project_id' => $project->id,
                        'batch_id' => $batchId,
                        'local_path' => null,
                        'remote_path' => $fileName,
                        'status' => 'success',
                        'trigger' => 'manual',
                        'message' => 'Project statement backed up successfully',
                        'triggered_by' => auth()->user()->name ?? 'system',
                        'file_hash' => md5($pdfContent),
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    // Log failure
                    SyncLog::create([
                        'project_id' => $project->id,
                        'batch_id' => $batchId,
                        'local_path' => null,
                        'remote_path' => null,
                        'status' => 'failed',
                        'trigger' => 'manual',
                        'message' => 'Failed to backup: ' . $e->getMessage(),
                        'triggered_by' => auth()->user()->name ?? 'system',
                        'error_trace' => $e->getTraceAsString(),
                    ]);

                    $failCount++;
                }
            }

            $message = "Backup completed: {$successCount} successful, {$failCount} failed";

            return redirect()
                ->route('profile.totalStatementAll')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()
                ->route('profile.totalStatementAll')
                ->with('error', 'Backup process failed: ' . $e->getMessage());
        }
    }
}
