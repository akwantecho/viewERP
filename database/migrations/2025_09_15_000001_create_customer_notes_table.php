<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->longText('html');
            $table->text('text')->nullable();
            $table->json('tags')->nullable();
            $table->string('color', 32)->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->string('visibility', 24)->default('team');
            $table->timestamp('pinned_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'is_pinned', 'created_at']);
            $table->index(['visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
    }
};

