<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->date('last_completed_date')->nullable()->after('longest_streak');
            $table->timestamp('streak_broken_at')->nullable()->after('last_completed_date');
            $table->integer('streak_before_break')->default(0)->after('streak_broken_at');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn(['last_completed_date', 'streak_broken_at', 'streak_before_break']);
        });
    }
};
