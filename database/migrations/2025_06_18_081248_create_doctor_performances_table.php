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
        Schema::create('doctor_performances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
        $table->integer('patients_seen');
        $table->decimal('rating', 2, 1);
        $table->text('remarks')->nullable();
        $table->date('report_date');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_performances');
    }
};
