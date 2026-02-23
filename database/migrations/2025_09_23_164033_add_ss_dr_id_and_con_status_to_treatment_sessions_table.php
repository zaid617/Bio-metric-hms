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
                $table->unsignedBigInteger('ss_dr_id')->nullable()->after('id');
                $table->tinyInteger('con_status')->default(0)->comment('0=pending, 1=ongoing, 2=completed, 3=cancelled')->after('ss_dr_id');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treatment_sessions', function (Blueprint $table) {
            $table->dropColumn(['ss_dr_id', 'con_status']);
        });
    }
};
