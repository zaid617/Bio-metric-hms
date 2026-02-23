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
       Schema::create('session_times', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('treatment_session_id');
        $table->dateTime('session_datetime')->nullable();
        $table->timestamps();

        // ✅ Foreign Key Constraint
        $table->foreign('treatment_session_id')
              ->references('id')
              ->on('treatment_sessions')
              ->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_times');
    }
};
