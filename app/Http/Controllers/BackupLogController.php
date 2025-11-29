<?php

namespace App\Http\Controllers;

use App\Services\BackupCenterService;

class BackupLogController extends Controller
{
    public function __construct(private readonly BackupCenterService $backupCenter)
    {
    }

    public function index()
    {
        $logs = $this->backupCenter->listBackupLogs();
        return view('backup.log', compact('logs'));
    }
}
