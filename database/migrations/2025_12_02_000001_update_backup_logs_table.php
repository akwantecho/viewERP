<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('backup_logs', 'runtime_duration')) {
                $table->decimal('runtime_duration', 10, 2)->nullable()->after('status');
            }
            if (!Schema::hasColumn('backup_logs', 'file_hash')) {
                $table->string('file_hash', 128)->nullable()->after('runtime_duration');
            }
            if (!Schema::hasColumn('backup_logs', 'cloud_url')) {
                $table->string('cloud_url', 2048)->nullable()->after('file_hash');
            }
            if (!Schema::hasColumn('backup_logs', 'triggered_by')) {
                $table->string('triggered_by')->nullable()->after('ran_by');
            }
            if (!Schema::hasColumn('backup_logs', 'error_trace')) {
                $table->longText('error_trace')->nullable()->after('message');
            }

            $table->index('created_at', 'backup_logs_created_at_index');
            $table->index('status', 'backup_logs_status_index');
            $table->index('triggered_by', 'backup_logs_triggered_by_index');
            $table->index('project_id', 'backup_logs_project_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('backup_logs', function (Blueprint $table) {
            if (Schema::hasColumn('backup_logs', 'runtime_duration')) {
                $table->dropColumn('runtime_duration');
            }
            if (Schema::hasColumn('backup_logs', 'file_hash')) {
                $table->dropColumn('file_hash');
            }
            if (Schema::hasColumn('backup_logs', 'cloud_url')) {
                $table->dropColumn('cloud_url');
            }
            if (Schema::hasColumn('backup_logs', 'triggered_by')) {
                $table->dropColumn('triggered_by');
            }
            if (Schema::hasColumn('backup_logs', 'error_trace')) {
                $table->dropColumn('error_trace');
            }

            $table->dropIndex('backup_logs_created_at_index');
            $table->dropIndex('backup_logs_status_index');
            $table->dropIndex('backup_logs_triggered_by_index');
            $table->dropIndex('backup_logs_project_id_index');
        });
    }
};
