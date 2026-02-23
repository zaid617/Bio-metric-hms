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
        $table->integer('session_count')->default(1);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treatment_sessions', function (Blueprint $table) {
        $table->dropColumn('session_count');
    });
    }
};
