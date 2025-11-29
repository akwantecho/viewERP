<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('full_backup_enabled')->default(true);
            $table->string('full_backup_frequency')->default('daily');
            $table->time('full_backup_time')->default('02:00:00');
            $table->string('full_backup_day_of_week')->nullable();
            $table->unsignedTinyInteger('full_backup_day_of_month')->nullable();
            $table->boolean('sync_enabled')->default(true);
            $table->string('sync_frequency')->default('weekly');
            $table->time('sync_time')->default('23:00:00');
            $table->string('sync_day_of_week')->nullable();
            $table->unsignedTinyInteger('sync_day_of_month')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
    }
};
