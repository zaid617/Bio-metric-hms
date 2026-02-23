<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->date('date');
            $table->string('day_of_week');

            // Morning shift
            $table->time('morning_start')->nullable();
            $table->time('morning_end')->nullable();
            $table->boolean('morning_leave')->default(false);

            // Evening shift
            $table->time('evening_start')->nullable();
            $table->time('evening_end')->nullable();
            $table->boolean('evening_leave')->default(false);

            $table->timestamps();

            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            $table->unique(['doctor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_availabilities');
    }
};
