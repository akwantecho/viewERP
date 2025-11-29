<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\BackupCenterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TotalStatementController extends Controller
{
    public function __construct(private readonly BackupCenterService $backupCenter)
    {
    }

    /**
     * تحميل PDF فقط (مشروع واحد)
     */
    public function exportStatementPdf(Project $project)
    {
        $units = $project->units()
            ->with(['floor', 'booking.installments', 'booking.payments', 'floor.project'])
            ->get();

        return Pdf::loadView('pdf.statement', [
            'project' => $project,
            'units'   => $units,
        ])->setPaper('a4', 'landscape')
            ->download("FinancialStatement-{$project->code}.pdf");
    }

    /**
     * رفع مشروع واحد إلى Google Drive
     */
    public function syncProjectStatement(Project $project, Request $request)
    {
        $userEmail = optional($request->user())->email;
        $outcome = $this->backupCenter->runProjectBackup($project, $userEmail, 'manual', false, $userEmail);

        if ($outcome->wasSuccessful()) {
            if ($outcome->hasRemoteCopy()) {
                return back()->with('success', 'Statement PDF backed up to ' . $outcome->remoteDisk . '.');
            }

            return back()->with('warning', 'Statement saved locally (no remote disk configured).');
        }

        return back()->with('error', 'فشل: ' . ($outcome->log->message ?? 'خطأ غير معروف'));
    }

    /**
     * رفع جميع المشاريع دفعة واحدة
     */
    public function syncAllProjects(Request $request)
    {
        $userEmail = optional($request->user())->email;
        $stats = $this->backupCenter->runAllProjectsBackup($userEmail, 'manual', false, $userEmail ?? 'system');

        if ($stats['failed'] === 0 && $stats['local_only'] === 0) {
            return back()->with('success', "Backed up {$stats['successful']} PDFs to remote disk successfully.");
        }

        if ($stats['failed'] === 0) {
            return back()->with('warning', "Generated {$stats['successful']} PDFs locally only (no remote disk).");
        }

        return back()->with('error', "Success {$stats['successful']} — Failed {$stats['failed']}.");
    }

    /**
     * عرض صفحة التوتل ستيتمنت لكل المشاريع
     */
    public function totalStatementAll()
    {
        $projects = Project::select('id', 'name', 'code')
            ->withCount('units')
            ->orderBy('name')
            ->get();

        $rows = $projects->map(fn ($p) => [
            'project_id'   => $p->id,
            'project_code' => $p->code,
            'project_name' => $p->name,
            'units_count'  => $p->units_count,
        ]);

        // جلب اللوجز بأمان: لو الجدول غير موجود لا نكسر الصفحة
        $logs = $this->backupCenter->listBackupLogs([], 15, 'logs_page');

        return view('reports.total_statement_all', compact('rows', 'logs'));
    }


    /**
     * توليد PDF لجميع المشاريع
     */
    public function exportTotalStatementAllPdf()
    {
        $projects = Project::with([
            'floors.units.booking.installments'
        ])->get();

        $rows = $projects->map(function ($project) {
            $units = $project->floors->flatMap->units;
            $totalPrice = $units->sum('base_price');
            $totalPaid  = $units->flatMap(fn($u) => optional($u->booking)->installments ?? collect())->sum('paid');

            return [
                'project_id'   => $project->id,
                'project_code' => $project->code,
                'project_name' => $project->name,
                'units_count'  => $units->count(),
                'total_price'  => (float) $totalPrice,
                'total_paid'   => (float) $totalPaid,
                'remaining'    => (float) ($totalPrice - $totalPaid),
            ];
        });

        $totals = [
            'total_price' => $rows->sum('total_price'),
            'total_paid'  => $rows->sum('total_paid'),
            'remaining'   => $rows->sum('remaining'),
        ];

        return Pdf::loadView('pdf.total_statement_all', [
            'rows' => $rows,
            'totals' => $totals,
            'generated_at' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'landscape')
            ->download('Total-Statement-All-Projects.pdf');
    }
}
