<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');                        // Judul tugas
            $table->text('description')->nullable();        // Deskripsi tugas
            $table->enum('type', [
                'tugas', 'kuis', 'ujian', 'presentasi', 'praktikum', 'lainnya'
            ])->default('tugas');
            $table->enum('status', [
                'belum', 'dikerjakan', 'selesai'
            ])->default('belum');
            $table->date('deadline')->nullable();
            $table->integer('nilai')->nullable();           // Nilai 0-100
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_assignments');
    }
};
