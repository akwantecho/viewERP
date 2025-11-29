<?php

$remoteBackupDisks = array_values(array_filter(array_map('trim', explode(',', env('BACKUP_REMOTE_DISKS', '')))));

if (empty($remoteBackupDisks)) {
    $defaultDisk = env('FILESYSTEM_DISK');
    $cloudDisk = env('FILESYSTEM_CLOUD');

    if ($defaultDisk && $defaultDisk !== 'local') {
        $remoteBackupDisks[] = $defaultDisk;
    }

    if ($cloudDisk) {
        $remoteBackupDisks[] = $cloudDisk;
    }
}

$backupDisks = array_values(array_unique(array_filter(array_merge(['local'], $remoteBackupDisks))));
$databaseConnections = array_values(array_filter(array_map('trim', explode(',', env('BACKUP_DB_CONNECTIONS', env('DB_CONNECTION', 'mysql'))))));

if (empty($databaseConnections)) {
    $databaseConnections = [env('DB_CONNECTION', 'mysql')];
}

return [

    'backup' => [
        'name' => env('APP_NAME', 'laravel-backup'),

        'source' => [
            'files' => [
                'include' => [
                    base_path('app'),
                    base_path('bootstrap'),
                    base_path('config'),
                    base_path('database'),
                    base_path('public'),
                    base_path('resources'),
                    base_path('routes'),
                    storage_path('app/public'),
                    base_path('.env'),
                ],

                'exclude' => [
                    base_path('node_modules'),
                    base_path('vendor'),
                    base_path('tests'),
                    storage_path('app/backup-temp'),
                    storage_path('framework/cache'),
                    storage_path('framework/sessions'),
                    storage_path('framework/testing'),
                    storage_path('logs'),
                ],

                'follow_links' => false,
                'ignore_unreadable_directories' => true,
                'relative_path' => base_path(),
            ],

            'databases' => $databaseConnections,
        ],

        'database_dump_compressor' => Spatie\DbDumper\Compressors\GzipCompressor::class,
        'database_dump_file_timestamp_format' => 'Y-m-d-His',
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => 'sql.gz',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => env('BACKUP_FILENAME_PREFIX', ''),
            'disks' => $backupDisks,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),
        'password' => env('BACKUP_ARCHIVE_PASSWORD', env('APP_KEY')),
        'encryption' => defined('ZipArchive::EM_AES_256') ? ZipArchive::EM_AES_256 : 'default',
        'tries' => 1,
        'retry_delay' => 0,
    ],

    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_TO', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Backup Center'),
            ],
        ],

        'slack' => [
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK', ''),
            'channel' => env('BACKUP_SLACK_CHANNEL'),
            'username' => 'Backup Bot',
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => env('BACKUP_DISCORD_WEBHOOK', ''),
            'username' => 'Backup Bot',
            'avatar_url' => '',
        ],
    ],

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'laravel-backup'),
            'disks' => $backupDisks,
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 10240,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 3,
            'keep_daily_backups_for_days' => 14,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 6,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 20480,
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],

];
