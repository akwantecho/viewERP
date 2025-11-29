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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('reservation_date')->nullable();
            $table->string('contract_file')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');

            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('agreed_base', 12, 2)->nullable();
            $table->decimal('agreed_vat', 12, 2)->nullable();
            $table->decimal('agreed_price', 12, 2)->nullable();
            $table->decimal('vat', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->nullable();
            $table->decimal('advance_payment', 12, 2)->default(0);
            $table->decimal('advance_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->nullable();

            $table->unsignedInteger('installments_count')->default(0);
            $table->unsignedTinyInteger('installment_frequency')->default(0);
            $table->unsignedTinyInteger('monthly_due_day')->nullable();
            $table->enum('plan_type', ['fixed', 'custom'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
