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
        Schema::table('doctors', function (Blueprint $table) {
            // Remove the 'name' column
            if (Schema::hasColumn('doctors', 'name')) {
                $table->dropColumn('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            // Add the 'name' column back if needed
            $table->string('name')->nullable()->after('last_name');
        });
    }
};
