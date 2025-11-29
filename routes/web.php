<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupLogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerNoteController;
use App\Http\Controllers\CustomerNoteUploadController;
use App\Http\Controllers\DirectSaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\Authorization\AuthorizationController as AdminAuthorizationController;
use App\Http\Controllers\Admin\Authorization\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\BackupCenterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\InstallmentNotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TotalStatementController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\S3FileController;
use Illuminate\Support\Facades\Route;



Route::pattern('locale', 'ar|en');

Route::redirect('/', '/en/login');

Route::prefix('{locale?}')
    ->where(['locale' => 'ar|en'])
    ->middleware('set.locale')
    ->group(function () {

Route::get('/', [HomeController::class, 'index'])->name('home')->defaults('locale', 'en');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->defaults('locale', 'en');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit')->defaults('locale', 'en');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Protected Routes (Authenticated Users)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users management
    Route::resource('users', UserController::class);

    // Pattern لمعرّف المشروع
    Route::pattern('project', '[0-9]+');

    // Projects (custom routes first to avoid conflicts with resource show route)
    Route::get('/projects/total-statement-all', [TotalStatementController::class, 'totalStatementAll'])
        ->name('projects.totalStatementAll');
    Route::get('/projects/{project}/statement', [ProjectController::class, 'statement'])->name('projects.statement');
    Route::get('/projects/{project}/statement/pdf', [TotalStatementController::class, 'exportStatementPdf'])->name('projects.statement.pdf');
    Route::get('/projects/{project}/statement/export', [TotalStatementController::class, 'exportStatementPdf'])
        ->name('projects.statement.export')->whereNumber('project');
    Route::get('/projects/{project}/structure', [ProjectController::class, 'structureForm'])->name('projects.structure');
    Route::post('/projects/{project}/structure', [ProjectController::class, 'saveStructure'])->name('projects.structure.save');
    Route::get('/projects/{project}/floors/create', [FloorController::class, 'create'])->name('floors.create');
    Route::post('/projects/{project}/floors', [FloorController::class, 'store'])->name('floors.store');
    Route::post('/projects/{project}/statement/backup', [TotalStatementController::class, 'syncProjectStatement'])
        ->middleware('super')->name('projects.statement.backup');
    Route::resource('projects', ProjectController::class);

    // Total Statement All under profile (admin area)
    Route::get('/profile/total-statement-all', [TotalStatementController::class, 'totalStatementAll'])
        ->name('profile.totalStatementAll');
    Route::get('/profile/total-statement-all/pdf', [TotalStatementController::class, 'exportTotalStatementAllPdf'])
        ->name('profile.totalStatementAll.pdf');
    Route::post('/profile/total-statement-all/sync', [SyncController::class, 'run'])
        ->name('profile.totalStatementAll.sync');
    // Backup all projects' statements
    Route::post('/profile/total-statement-all/backup-all', [TotalStatementController::class, 'syncAllProjects'])
        ->middleware('super')->name('profile.totalStatementAll.backupAll');

    Route::prefix('storage/files')->name('storage.files.')->middleware('super')->group(function () {
        Route::get('/', [S3FileController::class, 'index'])->name('index');
        Route::post('/upload', [S3FileController::class, 'store'])->name('store');
        Route::delete('/delete', [S3FileController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin/authorization')
        ->name('admin.authorization.')
        ->middleware('permission:roles.manage')
        ->group(function () {
            Route::get('/', [AdminAuthorizationController::class, 'index'])->name('index');
            Route::post('/sync-registry', [AdminAuthorizationController::class, 'syncRegistry'])->name('sync');

            Route::post('/roles', [AdminRoleController::class, 'store'])->name('roles.store');
            Route::put('/roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');
        });

    Route::prefix('admin/backup-center')
        ->name('admin.backup-center.')
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

    // Profile home and Files Management
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile/name', [ProfileController::class, 'updateName'])->name('profile.updateName');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');
    Route::get('/profile/activities', [ActivityController::class, 'index'])
        ->middleware('super')->name('profile.activities');
    Route::get('/profile/files', [SyncController::class, 'index'])->name('profile.files');

    // Customers
    Route::resource('customers', CustomerController::class);
    Route::get('/customers/{id}/profile', [CustomerController::class, 'profile'])->name('customers.profile');
    Route::post('/customers/{customer}/notes', [CustomerNoteController::class, 'store'])->name('customers.notes.store');
    Route::put('/customers/{customer}/notes/{note}', [CustomerNoteController::class, 'update'])->name('customers.notes.update');
    Route::delete('/customers/{customer}/notes/{note}', [CustomerNoteController::class, 'destroy'])->name('customers.notes.destroy');
    Route::post('/customers/{customer}/notes/upload', [CustomerNoteUploadController::class, 'store'])->name('customers.notes.upload');

    // Units
    Route::prefix('units')->name('units.')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('index');
        Route::get('/{unit}', [UnitController::class, 'show'])->name('show');
        Route::get('/{unit}/documents', [DocumentController::class, 'index'])->name('documents');
        Route::post('/{unit}/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::delete('/{unit}/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

        // Booking (نموذج + حفظ)
        Route::get('/{unit}/booking', [BookingController::class, 'form'])->name('booking.form');
        Route::post('/{unit}/booking', [BookingController::class, 'store'])->name('booking.store');

        // Direct sale
        Route::get('/{unit}/direct-sale', [DirectSaleController::class, 'form'])->name('direct-sale.form');
        Route::post('/{unit}/direct-sale', [DirectSaleController::class, 'store'])->name('direct-sale.store');

        Route::post('/{unit}/base-price', [UnitController::class, 'updatePrice'])->name('base-price.update');

        // عرض قسط معيّن داخل وحدة معيّنة
        Route::get('/{unit}/installments/{installment}', [InstallmentController::class, 'showForUnit'])
            ->name('installments.showForUnit');
    });

    /*
    |--------------------------------------------------------------------------
    | Installments (routes مستقلة - ليست داخل units)
    |--------------------------------------------------------------------------
    */
    Route::prefix('installments')->name('installments.')->group(function () {

        // قائمة أقساط "لحجز"
        Route::get('/booking/{booking}', [InstallmentController::class, 'index'])
            ->name('index'); // => /installments/booking/{booking}

        // صفحة دفع قسط (فيو pay)
        Route::get('/{installment}/pay', [InstallmentController::class, 'show'])->name('pay');

        // طباعة قسط واحد PDF
        Route::get('/{installment}/print', [InstallmentController::class, 'printInstallment'])->name('print');

        // تقرير الأقساط المدفوعة
        Route::get('/{booking}/report', [InstallmentController::class, 'printReport'])->name('report');

        // فاتورة جميع الأقساط المدفوعة
        Route::get('/{booking}/invoice', [InstallmentController::class, 'printInvoice'])->name('invoice');

        // الجدول الزمني للدفعات (PDF)
        Route::get('/{booking}/schedule-pdf', [InstallmentController::class, 'schedulePdf'])->name('schedule');
    });

    // Removed a greedy catch-all route which conflicted with other routes
    // Previously: Route::get('/{installment}', [InstallmentController::class, 'show'])->name('show');


    // Alias قديم (توافق رجعي): يحوّل /bookings/{booking}/installments إلى المسار الجديد
    Route::get('/bookings/{booking}/installments', function (\App\Models\Booking $booking) {
        return redirect()->route('installments.index', $booking);
    })->name('bookings.installments.index');

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */
    // Use scoped bindings so {payment} must belong to {booking}
    Route::prefix('bookings/{booking}')->scopeBindings()->group(function () {
        // جميع الدفعات للحجز
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');

        // إنشاء دفعة عامة (عربون)
        Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');

        // إنشاء دفعة لقسط معيّن
        Route::get('/installments/{installment}/payments/create', [PaymentController::class, 'create'])
            ->name('payments.create.installment');
        Route::post('/installments/{installment}/payments', [PaymentController::class, 'store'])
            ->name('payments.store.installment');

        // طباعة جميع دفعات الحجز PDF (عرّفه قبل المسارات الديناميكية)
        Route::get('/payments/print-all', [PaymentController::class, 'printAll'])->name('payments.printAll');

        // تعديل / حذف / عرض + طباعة PDF (تقييد المعرّف ليكون رقمياً)
        Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])
            ->whereNumber('payment')->name('payments.edit');
        Route::put('/payments/{payment}', [PaymentController::class, 'update'])
            ->whereNumber('payment')->name('payments.update');
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])
            ->middleware('permission:payments.delete')
            ->whereNumber('payment')->name('payments.destroy');

        // تفاصيل دفعة محددة + طباعة PDF
        Route::get('/payments/{payment}/invoice', [PaymentController::class, 'invoice'])
            ->whereNumber('payment')->name('payments.invoice');
        Route::get('/payments/{payment}', [PaymentController::class, 'show'])
            ->whereNumber('payment')->name('payments.show');
        Route::get('/payments/{payment}/print', [PaymentController::class, 'print'])
            ->whereNumber('payment')->name('payments.print');

        // خطة الأقساط منفصلة عن إنشاء الحجز
        Route::get('/installments/plan', [InstallmentController::class, 'plan'])
            ->name('bookings.installments.plan');
        Route::post('/installments/plan', [InstallmentController::class, 'storePlan'])
            ->name('bookings.installments.plan.store');
    });

    // Booking delete (admin/permission only)
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])
        ->middleware('permission:bookings.delete')
        ->name('bookings.destroy');

    // إشعارات الأقساط
    Route::get('/notifications', [InstallmentNotificationController::class, 'index'])
        ->name('installments.notifications');

    // Backup Log
    Route::get('/backup-log', [BackupLogController::class, 'index'])->name('backup-log.index');
});

/*
|--------------------------------------------------------------------------
| Sync (خارج auth)
|--------------------------------------------------------------------------
*/
Route::get('/sync/logs', [SyncController::class, 'index'])->name('sync.index');
Route::post('/sync/run', [SyncController::class, 'run'])->name('sync.run');

});

Route::fallback(function () {
    $path = trim(request()->path(), '/');
    if (! str_starts_with($path, 'ar') && ! str_starts_with($path, 'en')) {
        $suffix = $path ? '/' . $path : '';
        return redirect('/ar' . $suffix);
    }
    abort(404);
});
