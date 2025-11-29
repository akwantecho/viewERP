<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('model_has_roles') && ! Schema::hasTable('role_user')) {
            Schema::rename('model_has_roles', 'role_user');
        }

        if (! Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
                $table->primary(['permission_id', 'role_id']);
            });
        }

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->orderBy('role_id')->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('permission_role')->updateOrInsert([
                        'permission_id' => $row->permission_id,
                        'role_id' => $row->role_id,
                    ]);
                }
            });

            Schema::drop('role_has_permissions');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_user') && ! Schema::hasTable('model_has_roles')) {
            Schema::rename('role_user', 'model_has_roles');
        }

        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
                $table->primary(['permission_id', 'role_id']);
            });
        }
    }
};
