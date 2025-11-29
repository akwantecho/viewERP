<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\GoogleDriveServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        AppServiceProvider::class,
        GoogleDriveServiceProvider::class,
        AuthServiceProvider::class,
        PermissionServiceProvider::class,
    ])
    ->withCommands([
        \App\Console\Commands\MigrateReceiptsToS3::class,
        \App\Console\Commands\WeeklyStatementsSync::class,
        \App\Console\Commands\PermissionsSync::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'super' => \App\Http\Middleware\OnlySuper::class,
            'set.locale' => \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        \App\Support\BackupScheduler::register($schedule);
    })
    ->create();
