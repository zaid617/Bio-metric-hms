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
        Schema::table('doctor_availabilities', function (Blueprint $table) {
            if (!Schema::hasColumn('doctor_availabilities', 'day_of_week')) {
                $table->string('day_of_week');
            }
            if (Schema::hasColumn('doctor_availabilities', 'start_time')) {
                $table->time('start_time')->nullable()->change();
            }
            if (Schema::hasColumn('doctor_availabilities', 'end_time')) {
                $table->time('end_time')->nullable()->change();
            }
            if (!Schema::hasColumn('doctor_availabilities', 'is_leave')) {
                $table->boolean('is_leave')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_availabilities', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_availabilities', 'day_of_week')) {
                $table->dropColumn('day_of_week');
            }
            if (Schema::hasColumn('doctor_availabilities', 'is_leave')) {
                $table->dropColumn('is_leave');
            }
            if (Schema::hasColumn('doctor_availabilities', 'start_time')) {
                $table->time('start_time')->nullable(false)->change();
            }
            if (Schema::hasColumn('doctor_availabilities', 'end_time')) {
                $table->time('end_time')->nullable(false)->change();
            }
        });
    }
};
