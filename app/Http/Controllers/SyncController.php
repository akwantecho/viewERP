<?php

namespace App\Http\Controllers;

use App\Services\BackupCenterService;

class SyncController extends Controller
{
    public function __construct(private readonly BackupCenterService $backupCenter)
    {
    }

    public function index()
    {
        $logs = $this->backupCenter->listSyncLogs([], 10);
        return view('sync.index', compact('logs'));
    }

    public function run()
    {
        $stats = $this->backupCenter->runStatementsBackupJob('manual', optional(auth()->user())->email ?? 'system');

        if ($stats['failed'] === 0 && $stats['local_only'] === 0) {
            return redirect()->route('sync.index')->with('success', 'تمت المزامنة بنجاح');
        }

        if ($stats['failed'] === 0) {
            return redirect()->route('sync.index')->with('warning', 'تم إنشاء النسخ محلياً فقط، تحقق من إعدادات السحابة.');
        }

        return redirect()->route('sync.index')->with('error', 'فشلت بعض عمليات المزامنة، راجع السجلات.');
    }
}
