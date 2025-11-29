<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'project_id',
        'batch_id',
        'local_path',
        'remote_path',
        'status',
        'trigger',
        'message',
        'runtime_duration',
        'file_hash',
        'cloud_url',
        'triggered_by',
        'error_trace',
    ];

    protected $casts = [
        'runtime_duration' => 'float',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
