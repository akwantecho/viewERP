<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBackupSettingRequest;
use App\Models\BackupSetting;
use App\Models\Project;
use App\Services\BackupCenterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BackupCenterController extends Controller
{
    public function __construct(private readonly BackupCenterService $backupCenter)
    {
    }

    public function index(Request $request)
    {
        $settings = $this->resolveSettings();
        $backupLogs = $this->backupCenter->listBackupLogs([], 10, 'backup_logs_page');
        $syncLogs = $this->backupCenter->listSyncLogs([], 10, 'sync_logs_page');

        return view('admin.backup-center.index', [
            'activeTab' => $request->input('tab', 'backup_logs'),
            'settings' => $settings,
            'backupLogs' => $backupLogs,
            'syncLogs' => $syncLogs,
            'projectCount' => $this->resolveProjectCount(),
            'nextStatementsBackup' => $settings->nextStatementsBackupAt(),
            'latestBatchSummary' => $this->backupCenter->latestStatementBatchSummary(),
        ]);
    }

    public function backupLogs(Request $request)
    {
        $request->merge(['tab' => 'backup_logs']);
        return $this->index($request);
    }

    public function syncLogs(Request $request)
    {
        $request->merge(['tab' => 'sync_logs']);
        return $this->index($request);
    }

    public function backupStatementsToS3(Request $request): RedirectResponse
    {
        $userEmail = optional($request->user())->email;
        $stats = $this->backupCenter->backupStatementsForAllProjectsToS3($userEmail, 'dashboard', $userEmail ?? 'system');

        if ($stats['failed'] === 0) {
            $message = sprintf('Statements backup to S3 completed for %d projects.', $stats['successful']);
            return back()->with('success', $message)->with('statements_backup_summary', $stats);
        }

        $message = sprintf('Statements backup finished with %d failures. Review logs below.', $stats['failed']);
        return back()->with('error', $message)->with('statements_backup_summary', $stats);
    }

    public function download(Request $request): Response|RedirectResponse
    {
        $request->validate([
            'disk' => 'required|string',
            'path' => 'required|string',
        ]);

        try {
            $contents = $this->backupCenter->downloadBackup($request->string('disk'), $request->string('path'));
        } catch (Throwable $exception) {
            report($exception);
            return back()->with('error', 'Unable to download backup: ' . $exception->getMessage());
        }

        $filename = basename($request->string('path')) ?: 'backup.zip';

        return response($contents)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'disk' => 'required|string',
            'path' => 'required|string',
        ]);

        try {
            $this->backupCenter->deleteBackup($request->string('disk'), $request->string('path'));
            return back()->with('success', 'Backup deleted successfully.');
        } catch (Throwable $exception) {
            report($exception);
            return back()->with('error', 'Unable to delete backup: ' . $exception->getMessage());
        }
    }

    public function updateSettings(UpdateBackupSettingRequest $request): RedirectResponse
    {
        $settings = $this->resolveSettings();
        $settings->fill($request->validated());
        $settings->save();

        return back()->with('success', 'Backup settings updated.');
    }

    private function resolveSettings(): BackupSetting
    {
        try {
            if (! Schema::hasTable('backup_settings')) {
                return BackupSetting::make(BackupSetting::defaults());
            }

            return BackupSetting::current();
        } catch (Throwable $exception) {
            Log::warning('Failed to load backup settings', [
                'error' => $exception->getMessage(),
            ]);

            return BackupSetting::make(BackupSetting::defaults());
        }
    }

    private function resolveProjectCount(): int
    {
        return cache()->remember('backup_center_project_count', 300, fn () => (int) Project::count());
    }
}
