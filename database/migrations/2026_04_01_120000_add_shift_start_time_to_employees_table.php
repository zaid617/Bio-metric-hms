<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employees', 'shift_start_time')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->time('shift_start_time')->nullable()->after('shift');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'shift_start_time')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('shift_start_time');
            });
        }
    }
};
