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
        Schema::create('employee_salaries', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('employee_id');
    $table->date('month'); 
    $table->decimal('basic_salary', 10, 2);
    $table->decimal('allowances', 10, 2)->default(0);
    $table->decimal('deductions', 10, 2)->default(0);
    $table->decimal('bonuses', 10, 2)->default(0);
    $table->decimal('net_salary', 10, 2);
    $table->enum('payment_status', ['Pending', 'Paid'])->default('Pending');
    $table->date('paid_on')->nullable();
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
