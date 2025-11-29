<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\BackupSetting;
use App\Services\BackupCenterService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BackupScheduler
{
    public static function register(Schedule $schedule): void
    {
        $settings = self::settings();

        self::scheduleStatementsBackup($schedule, $settings);
    }

    private static function scheduleStatementsBackup(Schedule $schedule, BackupSetting $settings): void
    {
        if (! $settings->full_backup_enabled) {
            return;
        }

        $event = $schedule->call(function () {
            try {
                app(BackupCenterService::class)->runStatementsBackupJob('scheduler', 'scheduler');
            } catch (Throwable $exception) {
                Log::error('Scheduled statements backup failed', ['error' => $exception->getMessage()]);
                report($exception);
            }
        })->name('Project Statements Backup');

        self::applyFrequency(
            $event,
            $settings->full_backup_frequency,
            $settings->full_backup_time ?? '02:00',
            $settings->full_backup_day_of_week,
            $settings->full_backup_day_of_month
        );
    }

    private static function applyFrequency($event, string $frequency, string $time, ?string $dayOfWeek, ?int $dayOfMonth): void
    {
        $time = self::formatTime($time);

        switch ($frequency) {
            case 'weekly':
            case 'weekly_on_day':
                $event->weeklyOn(self::weeklyIndex($dayOfWeek), $time);
                break;
            case 'monthly':
                $event->monthlyOn(self::monthDay($dayOfMonth), $time);
                break;
            default:
                $event->dailyAt($time);
                break;
        }
    }

    private static function settings(): BackupSetting
    {
        try {
            if (! Schema::hasTable('backup_settings')) {
                return BackupSetting::make(BackupSetting::defaults());
            }

            return BackupSetting::current();
        } catch (Throwable $exception) {
            Log::warning('Falling back to default backup schedule configuration', [
                'error' => $exception->getMessage(),
            ]);

            return BackupSetting::make(BackupSetting::defaults());
        }
    }

    private static function formatTime(string $time): string
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, '00');
        return sprintf('%02d:%02d', (int) $hour, (int) $minute);
    }

    private static function weeklyIndex(?string $value): int
    {
        $map = [
            'sunday' => Carbon::SUNDAY,
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
        ];

        $value = strtolower((string) $value);
        return $map[$value] ?? Carbon::SATURDAY;
    }

    private static function monthDay(?int $day): int
    {
        $day = $day ?? 1;
        return max(1, min($day, 28));
    }
}
