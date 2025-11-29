<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_note_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_note_id')->constrained('customer_notes')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->longText('html');
            $table->text('text')->nullable();
            $table->json('tags')->nullable();
            $table->string('color', 32)->nullable();
            $table->string('visibility', 24)->default('team');
            $table->timestamps();

            $table->index(['customer_note_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_note_revisions');
    }
};

