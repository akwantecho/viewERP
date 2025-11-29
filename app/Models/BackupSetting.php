<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    protected $fillable = [
        'full_backup_enabled',
        'full_backup_frequency',
        'full_backup_time',
        'full_backup_day_of_week',
        'full_backup_day_of_month',
        'sync_enabled',
        'sync_frequency',
        'sync_time',
        'sync_day_of_week',
        'sync_day_of_month',
    ];

    protected $casts = [
        'full_backup_enabled' => 'boolean',
        'sync_enabled' => 'boolean',
        'full_backup_day_of_month' => 'integer',
        'sync_day_of_month' => 'integer',
    ];

    public function getFullBackupTimeAttribute($value): string
    {
        return $this->normalizeTime($value) ?? '02:00';
    }

    public function getSyncTimeAttribute($value): string
    {
        return $this->normalizeTime($value) ?? '23:00';
    }

    public static function defaults(): array
    {
        return [
            'full_backup_enabled' => true,
            'full_backup_frequency' => 'daily',
            'full_backup_time' => '02:00',
            'full_backup_day_of_week' => 'monday',
            'full_backup_day_of_month' => 1,
            'sync_enabled' => true,
            'sync_frequency' => 'weekly',
            'sync_time' => '23:00',
            'sync_day_of_week' => 'saturday',
            'sync_day_of_month' => 1,
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::create(static::defaults());
    }

    public function nextFullBackupAt(?Carbon $from = null): ?Carbon
    {
        if (! $this->full_backup_enabled) {
            return null;
        }

        return $this->calculateNextRun(
            frequency: $this->full_backup_frequency,
            time: $this->full_backup_time ?? '02:00',
            dayOfWeek: $this->full_backup_day_of_week,
            dayOfMonth: $this->full_backup_day_of_month
        );
    }

    public function nextStatementsBackupAt(?Carbon $from = null): ?Carbon
    {
        return $this->nextFullBackupAt($from);
    }

    public function nextSyncAt(?Carbon $from = null): ?Carbon
    {
        if (! $this->sync_enabled) {
            return null;
        }

        return $this->calculateNextRun(
            frequency: $this->sync_frequency,
            time: $this->sync_time ?? '23:00',
            dayOfWeek: $this->sync_day_of_week,
            dayOfMonth: $this->sync_day_of_month
        );
    }

    private function calculateNextRun(string $frequency, string $time, ?string $dayOfWeek, ?int $dayOfMonth, ?Carbon $from = null): Carbon
    {
        $from ??= now();
        [$hour, $minute] = array_pad(explode(':', $time), 2, '00');
        $hour = (int) $hour;
        $minute = (int) $minute;
        $candidate = $from->copy()->setTime($hour, $minute);

        return match ($frequency) {
            'weekly', 'weekly_on_day' => $this->nextWeekly($candidate, $from, $dayOfWeek),
            'monthly' => $this->nextMonthly($candidate, $from, $dayOfMonth),
            default => $this->nextDaily($candidate, $from),
        };
    }

    private function nextDaily(Carbon $candidate, Carbon $from): Carbon
    {
        if ($candidate->lessThanOrEqualTo($from)) {
            $candidate->addDay();
        }

        return $candidate;
    }

    private function nextWeekly(Carbon $candidate, Carbon $from, ?string $dayOfWeek): Carbon
    {
        $target = $this->dayOfWeekIndex($dayOfWeek);
        $diff = ($target - $from->dayOfWeek + 7) % 7;
        $candidate = $from->copy()->addDays($diff)->setTime($candidate->hour, $candidate->minute);

        if ($candidate->lessThanOrEqualTo($from)) {
            $candidate->addWeek();
        }

        return $candidate;
    }

    private function nextMonthly(Carbon $candidate, Carbon $from, ?int $day): Carbon
    {
        $day = $day ?? 1;
        $day = max(1, min($day, 28));
        $candidate = $from->copy()->setDay($day)->setTime($candidate->hour, $candidate->minute);

        if ($candidate->lessThanOrEqualTo($from)) {
            $candidate->addMonthNoOverflow()->setDay($day);
        }

        return $candidate;
    }

    private function dayOfWeekIndex(?string $value): int
    {
        $map = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        $value = strtolower((string) $value);
        return $map[$value] ?? 6;
    }

    private function normalizeTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        [$hour, $minute] = array_pad(explode(':', $value), 2, '00');

        return sprintf('%02d:%02d', (int) $hour, (int) $minute);
    }
}
