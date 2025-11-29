<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'agreement_file')) {
                $table->dropColumn('agreement_file');
            }

            if (Schema::hasColumn('customers', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'agreement_file')) {
                $table->string('agreement_file')->nullable()->after('id_type');
            }

            if (! Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable()->after('id_file');
            }
        });
    }
};
