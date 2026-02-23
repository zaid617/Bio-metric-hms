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
        Schema::table('treatment_sessions', function (Blueprint $table) {
            $table->tinyInteger('enrollment_status')
                  ->default(0)
                  ->comment('0 = Pending, 1 = Completed')
                  ->after('con_status'); // jahan chahte ho wahan place karo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treatment_sessions', function (Blueprint $table) {
            $table->dropColumn('enrollment_status');
        });
    }
};
