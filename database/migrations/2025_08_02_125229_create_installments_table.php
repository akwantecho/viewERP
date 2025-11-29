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
      Schema::create('installments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('booking_id')->constrained()->onDelete('cascade');
    $table->unsignedInteger('installment_number');
    $table->date('due_date');
    $table->decimal('amount', 12, 2); // بدون ضريبة
    $table->decimal('vat', 12, 2)->default(0.00); 
    $table->decimal('total_amount', 12, 2); // amount + vat
    $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
