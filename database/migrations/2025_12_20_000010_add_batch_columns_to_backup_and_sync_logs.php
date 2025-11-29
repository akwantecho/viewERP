<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('backup_logs', 'batch_id')) {
                $table->uuid('batch_id')->nullable()->after('triggered_by');
                $table->index('batch_id', 'backup_logs_batch_id_index');
            }
        });

        Schema::table('sync_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('sync_logs', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('sync_logs', 'batch_id')) {
                $table->uuid('batch_id')->nullable()->after('trigger');
                $table->index('batch_id', 'sync_logs_batch_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sync_logs', function (Blueprint $table) {
            if (Schema::hasColumn('sync_logs', 'batch_id')) {
                $table->dropIndex('sync_logs_batch_id_index');
                $table->dropColumn('batch_id');
            }

            if (Schema::hasColumn('sync_logs', 'project_id')) {
                $table->dropConstrainedForeignId('project_id');
            }
        });

        Schema::table('backup_logs', function (Blueprint $table) {
            if (Schema::hasColumn('backup_logs', 'batch_id')) {
                $table->dropIndex('backup_logs_batch_id_index');
                $table->dropColumn('batch_id');
            }
        });
    }
};
