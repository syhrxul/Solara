<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mata_kuliah');                  // Nama mata kuliah
            $table->string('kelas');                        // Kelas (A, B, dsb)
            $table->string('dosen')->nullable();            // Nama dosen
            $table->string('media_pembelajaran')->nullable(); // Zoom, Offline, dll
            $table->tinyInteger('sks')->default(2);        // SKS
            $table->tinyInteger('sesi')->nullable();        // Sesi ke-berapa
            $table->enum('hari', [
                'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'
            ]);
            $table->time('waktu_mulai');                    // Jam mulai
            $table->time('waktu_selesai')->nullable();      // Jam selesai
            $table->string('ruangan')->nullable();          // Ruangan/link zoom
            $table->boolean('is_active')->default(true);
            $table->string('semester')->nullable();         // Contoh: 2024/2025 Ganjil
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
