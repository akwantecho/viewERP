# Backup Center Architecture

## Overview
- `BackupCenterService` now focuses exclusively on statement backups: it renders Total Statement PDFs for each project, stores them locally, uploads to S3, and records analytics in `backup_logs` and `sync_logs`.
- The admin controller (`Admin\BackupCenterController`) exposes three concerns only: trigger statements backup to S3, manage the scheduler (frequency/time), and list backup/sync logs.
- `BackupSetting` retains the same columns but the `full_*` fields are interpreted as the "Statements Backup" schedule. `App\Support\BackupScheduler` reads those values and registers a single scheduled job that calls the service.
- UI lives at `/admin/backup-center` and now surfaces a single primary action (“Run Statements Backup to S3”), scheduler overview/settings, and the two log tabs.

## Statement Backup Flow
1. `BackupCenterService::backupStatementsForAllProjectsToS3()` fetches all projects, generates PDFs, writes them to `storage/app/public`, computes hashes, uploads to S3 under `statements/{project_id}/{timestamp}.pdf`, and creates `BackupLog` rows with a shared `batch_id`.
2. Each per-project upload also writes a `SyncLog` entry so the sync tab can highlight both manual and scheduled runs per project.
3. Aggregated stats (projects processed, successes, failures, runtime, batch id) are returned to controllers/commands for flash messages and scheduler decisions. `latestStatementBatchSummary()` inspects the most recent `batch_id` to power the dashboard card.

## Scheduler & Console
- `App\Support\BackupScheduler` registers a single scheduled callback named “Project Statements Backup” that delegates to `BackupCenterService::runStatementsBackupJob()`. Frequency/time/day-of-week/day-of-month come from `BackupSetting` (`full_*` columns) and can be configured in the UI.
- The console command `statements:sync` simply proxies to `runStatementsBackupJob`, allowing manual execution from CLI or other tooling.

## Logs & Monitoring
- `backup_logs` hold the authoritative per-project entries (project_id, disk `s3`, remote path, runtime, hash, status, triggered_by, batch_id). These rows are used for download/delete actions and for the “Backup Logs” tab.
- `sync_logs` record the same per-project runs but emphasise transport details (local path, remote path, trigger, runtime, error traces) so we can audit scheduled or manual runs separately. The new `project_id` + `batch_id` columns allow filtering/grouping and surfaces the project name in the UI.
- When the scheduler encounters failures during a run, `SyncJobFailed` notifications are sent to the email list defined in `config/backup-center.php` so admins know that some statements were not uploaded.

## Extensibility
- To support another cloud disk, simply define it in `config/filesystems.php` and extend `BackupCenterService::resolveRemoteDisk()` / `remoteVisibilityOptions()` to prioritise that disk instead of S3.
- The data-transfer object `BackupOutcome` keeps runtime/hash/paths centralized, making it easy to plug new telemetry or notification channels without touching controllers.
- `batch_id` on both log tables enables future batch-level dashboards (e.g., charts, KPIs, or exporting run summaries) without altering the controller/view contracts.
