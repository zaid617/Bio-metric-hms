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
        Schema::create('session_installments', function (Blueprint $table) {
              $table->id();
            $table->unsignedBigInteger('session_id');
            $table->decimal('amount', 8, 2);
            $table->date('payment_date');
            $table->string('payment_method')->nullable(); // e.g., cash, card
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('treatment_sessions')->onDelete('cascade');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_installments');
    }
};
