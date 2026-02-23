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
        Schema::table('checkups', function (Blueprint $table) {
            if (!Schema::hasColumn('checkups', 'checkup_status')) {
                $table->tinyInteger('checkup_status')->default(0);
            }
            if (!Schema::hasColumn('checkups', 'status')) {
                $table->tinyInteger('status')->default(1);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkups', function (Blueprint $table) {
            if (Schema::hasColumn('checkups', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('checkups', 'checkup_status')) {
                $table->dropColumn('checkup_status');
            }
        });
    }
};


