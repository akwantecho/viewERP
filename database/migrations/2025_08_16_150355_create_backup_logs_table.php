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
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()
                  ->constrained()->nullOnDelete();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('bytes')->nullable(); // حجم الملف
            $table->string('disk')->default('google');        // google / local
            $table->string('status')->default('success');     // success | failed
            $table->text('message')->nullable();
            $table->string('ran_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
