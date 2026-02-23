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
        Schema::create('treatment_session_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('treatment_session_id');
            $table->date('session_date');
            $table->time('session_time');
            $table->timestamps();

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
        Schema::dropIfExists('treatment_session_entries');
    }
};
