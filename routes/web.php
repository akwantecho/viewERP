<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\Authorization\AuthorizationController as AdminAuthorizationController;
use App\Http\Controllers\Admin\Authorization\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\BackupCenterController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupLogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerNoteController;
use App\Http\Controllers\CustomerNoteUploadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectSaleController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\InstallmentNotificationController;
use App\Http\Controllers\FallbackController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\S3FileController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TotalStatementController;
use App\Http\Controllers\InstallmentsReportController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::pattern('booking', '[0-9]+');
Route::pattern('project', '[0-9]+');
Route::pattern('customer', '[0-9]+');
Route::pattern('unit', '[0-9]+');
Route::pattern('installment', '[0-9]+');
Route::pattern('payment', '[0-9]+');
Route::pattern('note', '[0-9]+');
Route::pattern('document', '[0-9]+');

Route::middleware(['web', 'set.locale'])->group(function () {
    Route::redirect('/', '/login');

    Route::post('/language/switch', [LanguageController::class, 'switch'])
        ->name('language.switch');

    Route::middleware('guest')->group(function () {
        Route::get('/home', [HomeController::class, 'index'])
            ->name('home');

        Route::middleware('throttle:login')->group(function () {
            Route::get('/login', [AuthController::class, 'showLogin'])
                ->name('login');
            Route::post('/login', [AuthController::class, 'login'])
                ->name('login.submit');
        });
    });

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/reports/total-statement', [TotalStatementController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.totalStatement');

        Route::get('/reports/installments', [InstallmentsReportController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.installments');

        Route::resource('users', UserController::class);

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::prefix('authorization')
                ->name('authorization.')
                ->middleware('permission:roles.manage')
                ->group(function () {
                    Route::get('/', [AdminAuthorizationController::class, 'index'])->name('index');
                    Route::post('/sync-registry', [AdminAuthorizationController::class, 'syncRegistry'])->name('sync');
                    Route::post('/roles', [AdminRoleController::class, 'store'])->name('roles.store');
                    Route::put('/roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
                    Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');
                });

            Route::prefix('backup-center')
                ->name('backup-center.')
                ->middleware('super')
                ->group(function () {
                    Route::get('/', [BackupCenterController::class, 'index'])->name('index');
                    Route::get('/logs/backups', [BackupCenterController::class, 'backupLogs'])->name('logs.backups');
                    Route::get('/logs/sync', [BackupCenterController::class, 'syncLogs'])->name('logs.sync');
                    Route::post('/statements/backup', [BackupCenterController::class, 'backupStatementsToS3'])->name('statements.backup');
                    Route::get('/download', [BackupCenterController::class, 'download'])->name('download');
                    Route::delete('/delete', [BackupCenterController::class, 'destroy'])->name('delete');
                    Route::put('/settings', [BackupCenterController::class, 'updateSettings'])->name('settings.update');
                });
        });

        Route::prefix('storage/files')->name('storage.files.')->middleware('super')->group(function () {
            Route::get('/', [S3FileController::class, 'index'])->name('index');
            Route::post('/upload', [S3FileController::class, 'store'])->name('store');
            Route::delete('/delete', [S3FileController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::patch('/name', [ProfileController::class, 'updateName'])->name('updateName');
            Route::patch('/password', [ProfileController::class, 'updatePassword'])->name('updatePassword');
            Route::get('/activities', [ActivityController::class, 'index'])
                ->middleware('super')
                ->name('activities');
            Route::get('/files', [SyncController::class, 'index'])
                ->middleware('permission:sync.view')
                ->name('files');

            Route::prefix('total-statement-all')
                ->middleware('permission:reports.view')
                ->group(function () {
                    Route::get('/', [TotalStatementController::class, 'totalStatementAll'])
                        ->name('totalStatementAll');
                    Route::get('/pdf', [TotalStatementController::class, 'exportTotalStatementAllPdf'])
                        ->middleware('permission:reports.export')
                        ->name('totalStatementAll.pdf');
                    Route::post('/sync', [SyncController::class, 'run'])
                        ->middleware(['permission:sync.run', 'throttle:sync'])
                        ->name('totalStatementAll.sync');
                    Route::post('/backup-all', [TotalStatementController::class, 'syncAllProjects'])
                        ->middleware('super')
                        ->name('totalStatementAll.backupAll');
                });
        });

        Route::prefix('sync')
            ->name('sync.')
            ->middleware('permission:sync.view')
            ->group(function () {
                Route::get('/logs', [SyncController::class, 'index'])->name('index');
                Route::post('/run', [SyncController::class, 'run'])
                    ->middleware(['permission:sync.run', 'throttle:sync'])
                    ->name('run');
            });

        Route::prefix('projects')->name('projects.')->group(function () {
            Route::middleware('permission:reports.view')->group(function () {
                Route::get('/{project}/statement', [ProjectController::class, 'statement'])
                    ->whereNumber('project')
                    ->name('statement');
                Route::get('/{project}/statement/pdf', [TotalStatementController::class, 'exportStatementPdf'])
                    ->middleware('permission:reports.export')
                    ->whereNumber('project')
                    ->name('statement.pdf');
            });

            Route::middleware('permission:projects.manage')->group(function () {
                Route::get('/{project}/structure', [ProjectController::class, 'structureForm'])
                    ->whereNumber('project')
                    ->name('structure');
                Route::post('/{project}/structure', [ProjectController::class, 'saveStructure'])
                    ->whereNumber('project')
                    ->name('structure.save');
                Route::get('/{project}/floors/create', [FloorController::class, 'create'])
                    ->whereNumber('project')
                    ->name('floors.create');
                Route::post('/{project}/floors', [FloorController::class, 'store'])
                    ->whereNumber('project')
                    ->name('floors.store');
                Route::post('/{project}/statement/backup', [TotalStatementController::class, 'syncProjectStatement'])
                    ->middleware('super')
                    ->whereNumber('project')
                    ->name('statement.backup');
            });
        });

        Route::resource('projects', ProjectController::class)
            ->only(['index', 'show'])
            ->middleware('permission:projects.view');
        Route::resource('projects', ProjectController::class)
            ->only(['create', 'store', 'edit', 'update', 'destroy'])
            ->middleware('permission:projects.manage');

        Route::resource('customers', CustomerController::class)
            ->only(['index'])
            ->middleware('permission:customers.view');
        Route::resource('customers', CustomerController::class)
            ->only(['create', 'store', 'edit', 'update', 'destroy'])
            ->middleware('permission:customers.manage');
        Route::get('/customers/{customer}/profile', [CustomerController::class, 'profile'])
            ->whereNumber('customer')
            ->middleware('permission:customers.view')
            ->name('customers.profile');
        Route::get('/customers/{customer}', [CustomerController::class, 'profile'])
            ->whereNumber('customer')
            ->middleware('permission:customers.view')
            ->name('customers.show');

        // Customer Notes - simplified like other modules (NO policies)

        // View notes
        Route::resource('customers.notes', CustomerNoteController::class)
            ->shallow()
            ->only(['index'])
            ->names([
                'index' => 'customers.notes.index',
            ])
            ->middleware('permission:customers.view');

        // Manage notes
        Route::resource('customers.notes', CustomerNoteController::class)
            ->shallow()
            ->only(['create', 'store', 'edit', 'update', 'destroy'])
            ->names([
                'create' => 'customers.notes.create',
                'store' => 'customers.notes.store',
                'edit' => 'customers.notes.edit',
                'update' => 'customers.notes.update',
                'destroy' => 'customers.notes.destroy',
            ])
            ->middleware('permission:customers.manage');

        // Upload remains separate with light throttle
        Route::post('/customers/{customer}/notes/upload', [CustomerNoteUploadController::class, 'store'])
            ->whereNumber('customer')
            ->middleware(['permission:customers.manage', 'throttle:15,1'])
            ->name('customers.notes.upload');

        Route::prefix('units')->name('units.')->group(function () {
            Route::middleware('permission:units.view')->group(function () {
                Route::get('/', [UnitController::class, 'index'])->name('index');
                Route::get('/{unit}', [UnitController::class, 'show'])
                    ->whereNumber('unit')
                    ->name('show');
                Route::get('/{unit}/documents', [DocumentController::class, 'index'])
                    ->whereNumber('unit')
                    ->name('documents');
                Route::get('/{unit}/installments/{installment}', [InstallmentController::class, 'showForUnit'])
                    ->whereNumber('unit')
                    ->whereNumber('installment')
                    ->name('installments.showForUnit');
            });

            Route::middleware('permission:units.manage')->group(function () {
                Route::post('/{unit}/documents', [DocumentController::class, 'store'])
                    ->whereNumber('unit')
                    ->name('documents.store');
                Route::delete('/{unit}/documents/{document}', [DocumentController::class, 'destroy'])
                    ->whereNumber('unit')
                    ->whereNumber('document')
                    ->name('documents.destroy');
                Route::get('/{unit}/booking', [BookingController::class, 'form'])
                    ->whereNumber('unit')
                    ->name('booking.form');
                Route::post('/{unit}/booking', [BookingController::class, 'store'])
                    ->whereNumber('unit')
                    ->name('booking.store');
                Route::get('/{unit}/direct-sale', [DirectSaleController::class, 'form'])
                    ->whereNumber('unit')
                    ->name('direct-sale.form');
                Route::post('/{unit}/direct-sale', [DirectSaleController::class, 'store'])
                    ->whereNumber('unit')
                    ->name('direct-sale.store');
                Route::post('/{unit}/base-price', [UnitController::class, 'updatePrice'])
                    ->whereNumber('unit')
                    ->name('base-price.update');
            });
        });

        Route::prefix('installments')->name('installments.')->middleware('permission:installments.view')->group(function () {
            Route::get('/booking/{booking}', [InstallmentController::class, 'index'])
                ->whereNumber('booking')
                ->name('index');
            Route::get('/{installment}/pay', [InstallmentController::class, 'pay'])
                ->whereNumber('installment')
                ->name('pay');
            Route::get('/{installment}/print', [InstallmentController::class, 'printInstallment'])
                ->whereNumber('installment')
                ->name('print');
            Route::get('/{booking}/report', [InstallmentController::class, 'printReport'])
                ->whereNumber('booking')
                ->name('report');
            Route::get('/{booking}/invoice', [InstallmentController::class, 'printInvoice'])
                ->whereNumber('booking')
                ->name('invoice');
            Route::get('/{booking}/schedule-pdf', [InstallmentController::class, 'schedulePdf'])
                ->whereNumber('booking')
                ->name('schedule');
        });

        Route::prefix('bookings/{booking}')
            ->whereNumber('booking')
            ->scopeBindings()
            ->group(function () {
                Route::prefix('payments')->name('payments.')->group(function () {
                    Route::middleware('permission:payments.view')->group(function () {
                        Route::get('/', [PaymentController::class, 'index'])->name('index');
                        Route::get('/create', [PaymentController::class, 'create'])->name('create');
                        Route::get('/{payment}/edit', [PaymentController::class, 'edit'])
                            ->whereNumber('payment')
                            ->name('edit');
                        Route::get('/{payment}/invoice', [PaymentController::class, 'invoice'])
                            ->whereNumber('payment')
                            ->name('invoice');
                        Route::get('/{payment}', [PaymentController::class, 'show'])
                            ->whereNumber('payment')
                            ->name('show');
                        Route::get('/{payment}/print', [PaymentController::class, 'print'])
                            ->whereNumber('payment')
                            ->name('print');
                        Route::get('/print-all', [PaymentController::class, 'printAll'])->name('printAll');
                    });

                    Route::middleware('permission:payments.manage')->group(function () {
                        Route::post('/', [PaymentController::class, 'store'])->name('store');
                        Route::put('/{payment}', [PaymentController::class, 'update'])
                            ->whereNumber('payment')
                            ->name('update');
                        Route::delete('/{payment}', [PaymentController::class, 'destroy'])
                            ->whereNumber('payment')
                            ->middleware('permission:payments.delete')
                            ->name('destroy');
                    });
                });

                Route::prefix('installments/{installment}')
                    ->whereNumber('installment')
                    ->withoutScopedBindings()
                    ->group(function () {
                        Route::get('/payments', [InstallmentController::class, 'payForBooking'])
                            ->middleware('permission:payments.view')
                            ->name('payments.installments.pay');
                        Route::get('/payments/create', [PaymentController::class, 'create'])
                            ->middleware('permission:payments.view')
                            ->name('payments.create.installment');
                        Route::post('/payments', [PaymentController::class, 'store'])
                            ->middleware('permission:payments.manage')
                            ->name('payments.store.installment');
                    });

                Route::get('/installments/plan', [InstallmentController::class, 'plan'])
                    ->middleware('permission:installments.view')
                    ->name('bookings.installments.plan');
                Route::post('/installments/plan', [InstallmentController::class, 'storePlan'])
                    ->middleware('permission:installments.manage')
                    ->name('bookings.installments.plan.store');
            });

        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])
            ->whereNumber('booking')
            ->middleware('permission:bookings.delete')
            ->name('bookings.destroy');

        Route::get('/notifications', [InstallmentNotificationController::class, 'index'])
            ->middleware('permission:installments.view')
            ->name('installments.notifications');

        Route::get('/backup-log', [BackupLogController::class, 'index'])
            ->middleware('permission:backup.view')
            ->name('backup-log.index');
    });

    Route::fallback(FallbackController::class);
});
