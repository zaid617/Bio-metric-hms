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
        Schema::create('payments_outstandings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('session_id');
        $table->unsignedBigInteger('checkup_id');
        $table->string('payment_details');
        $table->timestamps();

        // Optional: add foreign key constraints
        $table->foreign('session_id')->references('id')->on('treatment_sessions')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments_outstandings');
    }
};
