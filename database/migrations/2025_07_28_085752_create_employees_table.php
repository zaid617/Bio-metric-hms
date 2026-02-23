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
       Schema::create('employees', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('designation');
    $table->unsignedBigInteger('branch_id');
    $table->decimal('basic_salary', 10, 2);
    $table->string('phone')->nullable();
    $table->date('joining_date')->nullable();
    $table->timestamps();

    $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
