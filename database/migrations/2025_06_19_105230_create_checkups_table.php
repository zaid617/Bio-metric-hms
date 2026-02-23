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
       Schema::create('checkups', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('patient_id');
    $table->string('diagnosis');
    $table->string('doctor')->nullable(); // Add doctor column
    $table->decimal('fee', 10, 2)->nullable(); // Add fee column
    $table->tinyInteger('checkup_status')->default(0); // 0=pending, 1=complete, 2=cancel
    $table->tinyInteger('status')->default(1); // 1=completed (active)
    $table->timestamps();

    $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkups');
    }
};
