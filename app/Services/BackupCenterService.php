<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\BackupOutcome;
use App\Models\BackupLog;
use App\Models\Project;
use App\Models\SyncLog;
use App\Notifications\ProjectBackupFailed;
use App\Notifications\SyncJobFailed;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BackupCenterService
{
    public function __construct(private readonly FilesystemFactory $filesystem)
    {
    }

    /**
     * Generate, store, and optionally upload a single project statement PDF.
     *
     * @return BackupOutcome
     */
    public function runProjectBackup(
        Project $project,
        ?string $performedBy = null,
        string $trigger = 'manual',
        bool $requireRemote = false,
        ?string $triggeredBy = null,
        ?string $batchId = null
    ): BackupOutcome
    {
        $triggeredBy = $this->normalizeTriggeredBy($triggeredBy ?? $performedBy);
        $fileName = sprintf('FinancialStatement-%s-%s.pdf', $project->code, now()->format('Ymd-His'));
        $relativeLocalPath = 'reports/' . $fileName;
        $absoluteLocalPath = storage_path('app/public/' . $relativeLocalPath);
        $remoteDisk = null;
        $remotePath = null;
        $cloudUrl = null;
        $errorTrace = null;
        $fileHash = null;
        $startedAt = microtime(true);
        $runtime = 0.0;

        try {
            $units = $project->units()
                ->with(['floor', 'booking.installments', 'booking.payments', 'floor.project'])
                ->get();

            $pdf = Pdf::loadView('pdf.statement', [
                'project' => $project,
                'units' => $units,
            ])->setPaper('a4', 'landscape');

            $contents = $pdf->output();
            Storage::disk('public')->put($relativeLocalPath, $contents);
            $fileHash = hash('sha256', $contents);
            $cloudUrl = $this->localUrl($relativeLocalPath);

            $remoteDisk = $this->resolveRemoteDisk();
            if ($requireRemote && ! $remoteDisk) {
                throw new RuntimeException('Remote backup disk is not configured.');
            }
            if ($remoteDisk) {
                $remotePath = $this->buildRemoteStatementPath($project);
                $this->filesystem->disk($remoteDisk)->put($remotePath, $contents, $this->remoteVisibilityOptions($remoteDisk));
                $cloudUrl = $this->resolveCloudUrl($remoteDisk, $remotePath);
            }

            $runtime = $this->runtimeSeconds($startedAt);
            $log = BackupLog::create([
                'project_id' => $project->id,
                'file_name' => $remotePath ?? $relativeLocalPath,
                'bytes' => strlen($contents),
                'disk' => $remoteDisk ?? 'public',
                'status' => 'success',
                'message' => $remoteDisk
                    ? sprintf('Statement backed up to %s (%s)', strtoupper($remoteDisk), $remotePath)
                    : 'Statement stored locally only',
                'ran_by' => $performedBy,
                'runtime_duration' => $runtime,
                'file_hash' => $fileHash,
                'cloud_url' => $cloudUrl,
                'triggered_by' => $triggeredBy,
                'error_trace' => null,
                'batch_id' => $batchId,
            ]);
        } catch (Throwable $exception) {
            $errorTrace = $exception->getTraceAsString();
            $runtime = $this->runtimeSeconds($startedAt);
            $log = BackupLog::create([
                'project_id' => $project->id,
                'file_name' => $relativeLocalPath,
                'bytes' => null,
                'disk' => $remoteDisk ?? 'public',
                'status' => 'failed',
                'message' => $exception->getMessage(),
                'ran_by' => $performedBy,
                'runtime_duration' => $runtime,
                'file_hash' => $fileHash,
                'cloud_url' => $cloudUrl,
                'triggered_by' => $triggeredBy,
                'error_trace' => $errorTrace,
                'batch_id' => $batchId,
            ]);

            Log::error('Project backup failed', [
                'location' => __METHOD__,
                'project_id' => $project->id,
                'trigger' => $trigger,
                'error' => $exception->getMessage(),
            ]);

            report($exception);
            $this->notifyAdmins(new ProjectBackupFailed($project, $log, $exception->getMessage()));
        }

        return new BackupOutcome(
            log: $log,
            localPath: $absoluteLocalPath,
            runtimeSeconds: $runtime,
            fileHash: $fileHash,
            remoteDisk: $remoteDisk,
            remotePath: $remotePath,
            cloudUrl: $cloudUrl,
            errorTrace: $errorTrace,
        );
    }

    /**
     * Run the statement backup for every project and return aggregate stats.
     *
     * @param  callable(Project, BackupOutcome, string):void|null  $afterEach  Hook executed per project.
     * @return array{projects:int,successful:int,failed:int,remote:int,local_only:int,batch_id:string}
     */
    public function runAllProjectsBackup(
        ?string $performedBy = null,
        string $trigger = 'manual',
        bool $requireRemote = false,
        ?string $triggeredBy = null,
        ?callable $afterEach = null,
        ?string $batchId = null
    ): array
    {
        $projects = Project::orderBy('name')->get();
        $batchId ??= (string) Str::uuid();
        $stats = [
            'projects' => $projects->count(),
            'successful' => 0,
            'failed' => 0,
            'remote' => 0,
            'local_only' => 0,
            'batch_id' => $batchId,
        ];

        foreach ($projects as $project) {
            $outcome = $this->runProjectBackup($project, $performedBy, $trigger, $requireRemote, $triggeredBy, $batchId);

            if ($afterEach) {
                $afterEach($project, $outcome, $batchId);
            }

            if ($outcome->wasSuccessful()) {
                $stats['successful']++;
                if ($outcome->hasRemoteCopy()) {
                    $stats['remote']++;
                } else {
                    $stats['local_only']++;
                }
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Run statement backups for every project and enforce uploading to S3.
     *
     * @return array{projects:int,successful:int,failed:int,remote:int,local_only:int,batch_id:string,runtime:float}
     */
    public function backupStatementsForAllProjectsToS3(
        ?string $performedBy = null,
        string $trigger = 'manual',
        ?string $triggeredBy = null
    ): array {
        $triggeredBy = $this->normalizeTriggeredBy($triggeredBy ?? $performedBy ?? $trigger);
        $startedAt = microtime(true);

        $stats = $this->runAllProjectsBackup(
            performedBy: $performedBy,
            trigger: $trigger,
            requireRemote: true,
            triggeredBy: $triggeredBy,
            afterEach: function (Project $project, BackupOutcome $outcome, string $batchId) use ($trigger): void {
                $this->recordSyncLog($project, $outcome, $trigger, $batchId);
            }
        );

        $stats['runtime'] = $this->runtimeSeconds($startedAt);

        return $stats;
    }

    /**
     * Execute the scheduled weekly sync, logging entries into sync_logs.
     *
     * @return array{projects:int,successful:int,failed:int,remote:int,local_only:int}
     */
    public function runStatementsBackupJob(string $trigger = 'scheduler', ?string $triggeredBy = null): array
    {
        $triggeredBy = $this->normalizeTriggeredBy($triggeredBy ?? $trigger);
        $stats = $this->backupStatementsForAllProjectsToS3(
            performedBy: $trigger === 'scheduler' ? null : $triggeredBy,
            trigger: $trigger,
            triggeredBy: $triggeredBy
        );

        if ($trigger === 'scheduler' && $stats['failed'] > 0) {
            $this->notifyAdmins(new SyncJobFailed($stats));
        }

        return $stats;
    }

    /**
     * Temporary backward compatible wrapper.
     */
    public function runWeeklySync(string $trigger = 'scheduler', ?string $triggeredBy = null): array
    {
        return $this->runStatementsBackupJob($trigger, $triggeredBy);
    }

    public function latestStatementBatchSummary(): ?array
    {
        try {
            $latest = BackupLog::whereNotNull('batch_id')
                ->latest('created_at')
                ->first();

            if (! $latest) {
                return null;
            }

            $batchLogs = BackupLog::where('batch_id', $latest->batch_id)->get();
            if ($batchLogs->isEmpty()) {
                return null;
            }

            $successful = $batchLogs->where('status', 'success')->count();
            $failed = $batchLogs->where('status', 'failed')->count();
            $ranAt = optional($batchLogs->sortByDesc('created_at')->first())->created_at;

            return [
                'batch_id' => $latest->batch_id,
                'ran_at' => $ranAt,
                'projects' => $batchLogs->count(),
                'successful' => $successful,
                'failed' => $failed,
                'disk' => 's3',
                'status' => $failed === 0 ? 'success' : 'failed',
            ];
        } catch (Throwable $exception) {
            Log::warning('Unable to derive latest statements backup summary', [
                'error' => $exception->getMessage(),
            ]);
            report($exception);

            return null;
        }
    }

    /**
     * Paginated list of backup logs with optional filters.
     *
     * @param  array{project_id?:int,status?:string,disk?:string}  $filters
     * @return LengthAwarePaginator
     */
    public function listBackupLogs(array $filters = [], int $perPage = 20, string $pageName = 'page'): LengthAwarePaginator
    {
        try {
            $query = BackupLog::with('project')->latest();

            if (isset($filters['project_id'])) {
                $query->where('project_id', $filters['project_id']);
            }
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['disk'])) {
                $query->where('disk', $filters['disk']);
            }

            return $query->paginate($perPage, ['*'], $pageName);
        } catch (Throwable $exception) {
            Log::error('Could not list backup logs', [
                'location' => __METHOD__,
                'error' => $exception->getMessage(),
            ]);

            report($exception);

            return $this->emptyPaginator($perPage, $pageName);
        }
    }

    /**
     * Paginated list of sync logs.
     *
     * @param  array{status?:string,trigger?:string}  $filters
     * @return LengthAwarePaginator
     */
    public function listSyncLogs(array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        try {
            $query = SyncLog::with('project')->latest();

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['trigger'])) {
                $query->where('trigger', $filters['trigger']);
            }
            if (isset($filters['triggered_by'])) {
                $query->where('triggered_by', $filters['triggered_by']);
            }

            return $query->paginate($perPage, ['*'], $pageName);
        } catch (Throwable $exception) {
            Log::error('Could not list sync logs', [
                'location' => __METHOD__,
                'error' => $exception->getMessage(),
            ]);

            report($exception);

            return $this->emptyPaginator($perPage, $pageName);
        }
    }

    /**
     * Retrieve the most recent backup entries for a project.
     */
    public function getProjectBackupHistory(Project $project, int $limit = 50): EloquentCollection
    {
        try {
            return BackupLog::with('project')
                ->where('project_id', $project->id)
                ->latest()
                ->limit($limit)
                ->get();
        } catch (Throwable $exception) {
            Log::error('Could not fetch project backup history', [
                'location' => __METHOD__,
                'project_id' => $project->id,
                'error' => $exception->getMessage(),
            ]);
            report($exception);

            return new EloquentCollection();
        }
    }

    /**
     * Upload an existing local file to the requested disk.
     *
     * @throws \InvalidArgumentException
     */
    public function uploadToCloud(string $localPath, string $disk, string $targetPath, array $options = []): bool
    {
        if (! file_exists($localPath)) {
            throw new \InvalidArgumentException('Local file not found: ' . $localPath);
        }

        $adapter = $this->filesystem($disk);
        $stream = fopen($localPath, 'r');
        $adapter->put($targetPath, $stream, $options);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return true;
    }

    /**
     * Delete a backup from a given disk.
     */
    public function deleteBackup(string $disk, string $path): bool
    {
        $adapter = $this->filesystem($disk);
        return $adapter->delete($path);
    }

    /**
     * Download a backup file contents.
     */
    public function downloadBackup(string $disk, string $path): string
    {
        $adapter = $this->filesystem($disk);
        return (string) $adapter->get($path);
    }

    private function resolveRemoteDisk(): ?string
    {
        if (config('filesystems.disks.s3')) {
            return 's3';
        }

        return null;
    }

    private function buildRemoteStatementPath(Project $project): string
    {
        return sprintf(
            'statements/%s/%s.pdf',
            $project->id,
            now()->format('Y-m-d_H-i-s')
        );
    }

    private function remoteVisibilityOptions(string $disk): array
    {
        if ($disk === 's3') {
            return ['visibility' => 'public'];
        }

        return [];
    }

    private function recordSyncLog(Project $project, BackupOutcome $outcome, string $trigger, string $batchId): void
    {
        SyncLog::create([
            'batch_id' => $batchId,
            'project_id' => $project->id,
            'local_path' => $outcome->localPath,
            'remote_path' => $outcome->remotePath ?? '-',
            'status' => $outcome->log->status,
            'trigger' => $trigger,
            'triggered_by' => $outcome->log->triggered_by ?? $trigger,
            'runtime_duration' => $outcome->runtimeSeconds,
            'file_hash' => $outcome->fileHash,
            'cloud_url' => $outcome->cloudUrl,
            'error_trace' => $outcome->errorTrace,
            'message' => $outcome->hasRemoteCopy()
                ? sprintf('Uploaded %s to %s', $project->code, $outcome->remoteDisk)
                : sprintf('Generated local copy only (%s)', $project->code),
        ]);
    }

    private function localUrl(string $path): ?string
    {
        try {
            return Storage::disk('public')->url($path);
        } catch (Throwable $exception) {
            Log::warning('Unable to build local file URL', ['path' => $path, 'error' => $exception->getMessage()]);
        }

        return null;
    }

    private function resolveCloudUrl(string $disk, string $path): ?string
    {
        try {
            return Storage::disk($disk)->url($path);
        } catch (Throwable $exception) {
            Log::warning('Unable to build cloud file URL', ['disk' => $disk, 'path' => $path, 'error' => $exception->getMessage()]);
        }

        return null;
    }

    private function runtimeSeconds(float $startedAt): float
    {
        return round(max(microtime(true) - $startedAt, 0), 2);
    }

    private function normalizeTriggeredBy(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : 'system';
    }

    private function notifyAdmins(BaseNotification $notification): void
    {
        $emails = config('backup-center.notifications.emails', []);

        foreach ($emails as $email) {
            Notification::route('mail', $email)->notify($notification);
        }
    }

    private function filesystem(string $disk): Filesystem
    {
        if (! config('filesystems.disks.' . $disk)) {
            throw new \InvalidArgumentException('Filesystem disk [' . $disk . '] is not configured.');
        }

        return $this->filesystem->disk($disk);
    }

    private function emptyPaginator(int $perPage, string $pageName = 'page'): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: [],
            total: 0,
            perPage: $perPage,
            currentPage: 1,
            options: [
                'path' => request()->url(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ]
        );
    }
}
