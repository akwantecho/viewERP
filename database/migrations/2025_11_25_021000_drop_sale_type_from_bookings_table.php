<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bookings', 'sale_type')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('sale_type');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('bookings', 'sale_type')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->enum('sale_type', ['cash', 'installment'])->nullable();
            });
        }
    }
};
