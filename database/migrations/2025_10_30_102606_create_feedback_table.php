<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sessionsid'); // session ID
            $table->unsignedBigInteger('doctorid')->nullable(); // doctor ID
            $table->unsignedBigInteger('patientid')->nullable(); // patient ID
            $table->text('doctor_remarks')->nullable(); // doctor remarks
            $table->text('patient_remarks')->nullable(); // patient remarks
            $table->integer('satisfaction')->nullable(); // satisfaction %
            $table->tinyInteger('status')->default(0); // 0 = pending, 1 = completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
