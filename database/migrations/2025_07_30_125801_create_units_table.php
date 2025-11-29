<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::create('units', function (Blueprint $table) {
        $table->id();
        $table->foreignId('floor_id')->constrained()->onDelete('cascade');
        $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null'); // في حالة تم البيع
        $table->string('unit_code')->unique();
        $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
        $table->decimal('base_price', 12, 2)->nullable();
        $table->string('contract_file')->nullable();
        $table->json('other_files')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
