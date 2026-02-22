<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            
            // Tipe data (sleep, steps, calories, spo2, dll)
            $table->string('type'); 
            
            // Value utama (contoh: total durasi tidur dalam jam, atau jumlah kalori)
            $table->decimal('value', 10, 2); 
            
            // Simpan data lengkapnya dalam JSON (contoh JSON berisi rem, light, deep, time_bed, time_wakeup)
            $table->json('details')->nullable(); 

            $table->timestamps();
            
            // Index dan Unique constraint
            $table->unique(['user_id', 'date', 'type']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_metrics');
    }
};
