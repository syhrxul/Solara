<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->default('heroicon-o-star');
            $table->string('color')->default('#10b981');
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->json('frequency_days')->nullable(); // [1,2,3,4,5] for Mon-Fri
            $table->integer('target_count')->default(1); // e.g. drink 8 glasses
            $table->string('unit')->nullable(); // e.g. "glasses", "minutes"
            $table->time('reminder_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->date('started_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habits');
    }
};
