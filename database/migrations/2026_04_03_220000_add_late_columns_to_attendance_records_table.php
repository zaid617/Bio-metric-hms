<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'is_late')) {
                $table->boolean('is_late')->default(false)->after('overtime_minutes');
            }

            if (!Schema::hasColumn('attendance_records', 'late_minutes')) {
                $table->unsignedInteger('late_minutes')->default(0)->after('is_late');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'late_minutes')) {
                $table->dropColumn('late_minutes');
            }

            if (Schema::hasColumn('attendance_records', 'is_late')) {
                $table->dropColumn('is_late');
            }
        });
    }
};
