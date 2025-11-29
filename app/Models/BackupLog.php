<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    // اسم الجدول (اختياري لأن الاسم افتراضي صحيح)
    protected $table = 'backup_logs';

    // اسمح بالحفظ الجماعي لكل الحقول (أسهل للّوجز)
    protected $guarded = [];

    protected $casts = [
        'bytes' => 'integer',
        'runtime_duration' => 'float',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
