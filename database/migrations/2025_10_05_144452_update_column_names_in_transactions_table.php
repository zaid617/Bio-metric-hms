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
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('p_id', 'patient_id');
            $table->renameColumn('dr_id', 'doctor_id');
            $table->renameColumn('entery_by', 'entry_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('patient_id', 'p_id');
            $table->renameColumn('doctor_id', 'dr_id');
            $table->renameColumn('entry_by', 'entery_by');
        });
    }
};
