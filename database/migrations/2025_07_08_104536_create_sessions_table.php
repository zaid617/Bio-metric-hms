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
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('checkup_id');
            $table->integer('session_number');
            $table->date('date');
            $table->time('time');
            $table->unsignedBigInteger('doctor_id');
            $table->string('status')->default('scheduled'); // scheduled, completed, missed
            $table->timestamps();

            // Foreign Keys
            $table->foreign('checkup_id')->references('id')->on('checkups')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
