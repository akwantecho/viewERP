<?php

namespace App\Console\Commands;

use App\Services\BackupCenterService;
use Illuminate\Console\Command;

class WeeklyStatementsSync extends Command
{
    protected $signature = 'statements:sync {--trigger=scheduler}';
    protected $description = 'Upload project statements to S3 based on the configured schedule';

    public function handle(BackupCenterService $service)
    {
        $trigger = $this->option('trigger');
        $stats = $service->runStatementsBackupJob($trigger, $trigger === 'scheduler' ? 'scheduler' : 'system');
        $summary = sprintf(
            'Projects: %d | Success: %d | Failed: %d | Remote: %d | Local only: %d',
            $stats['projects'],
            $stats['successful'],
            $stats['failed'],
            $stats['remote'],
            $stats['local_only']
        );

        if ($stats['failed'] > 0) {
            $this->error('Weekly statements sync finished with failures. ' . $summary);
        } elseif ($stats['local_only'] > 0) {
            $this->warn('Weekly statements sync stored locally only. ' . $summary);
        } else {
            $this->info('Weekly statements sync executed. ' . $summary);
        }
    }
}
