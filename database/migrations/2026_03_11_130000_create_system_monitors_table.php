<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // CPU
            $table->decimal('cpu_usage', 6, 2);
            $table->integer('cpu_cores');
            $table->string('cpu_model');
            $table->decimal('cpu_user', 6, 2);
            $table->decimal('cpu_system', 6, 2);
            $table->decimal('cpu_idle', 6, 2);

            // Memory
            $table->unsignedBigInteger('mem_total');
            $table->unsignedBigInteger('mem_used');
            $table->unsignedBigInteger('mem_free');
            $table->decimal('mem_usage_percent', 6, 2);
            $table->unsignedBigInteger('swap_total');
            $table->unsignedBigInteger('swap_used');

            // Disk
            $table->unsignedBigInteger('disk_total');
            $table->unsignedBigInteger('disk_used');
            $table->unsignedBigInteger('disk_free');
            $table->decimal('disk_usage_percent', 6, 2);

            // Network
            $table->unsignedBigInteger('net_bytes_in');
            $table->unsignedBigInteger('net_bytes_out');
            $table->decimal('net_speed_in', 12, 2);
            $table->decimal('net_speed_out', 12, 2);

            // Uptime
            $table->integer('uptime_seconds');
            $table->string('uptime_formatted');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_monitors');
    }
};
