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
        $table->dropForeign(['checkup_id']); // if foreign key constraint exists
        $table->dropColumn('checkup_id');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('treatment_sessions', function (Blueprint $table) {
        $table->unsignedBigInteger('checkup_id')->nullable();
        $table->foreign('checkup_id')->references('id')->on('checkups')->onDelete('set null');
    });
    }
};
