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
            if (!Schema::hasColumn('checkups', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('doctor_id');
            }

            if (!Schema::hasColumn('checkups', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkups', function (Blueprint $table) {
            if (Schema::hasColumn('checkups', 'updated_by')) {
                $table->dropColumn('updated_by');
            }

            if (Schema::hasColumn('checkups', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};
