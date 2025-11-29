<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role_id')) {
            return;
        }

        if (Schema::hasTable('role_user')) {
            DB::table('users')->whereNotNull('role_id')->orderBy('id')->chunk(200, function ($users) {
                foreach ($users as $user) {
                    DB::table('role_user')->updateOrInsert([
                        'role_id' => $user->role_id,
                        'model_id' => $user->id,
                        'model_type' => User::class,
                    ]);
                }
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->constrained('roles')->cascadeOnDelete();
            });
        }
    }
};
