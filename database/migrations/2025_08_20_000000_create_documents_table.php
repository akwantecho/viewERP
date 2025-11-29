<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            // Polymorphic relation to Unit, Booking, Customer
            $table->morphs('documentable'); // creates documentable_type, documentable_id indexed
            $table->string('name');
            $table->string('type')->nullable(); // contract, id, payment_receipt, other
            $table->string('path'); // URL or storage path
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

