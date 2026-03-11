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
        Schema::create('attendance_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->date('payroll_period_start');
            $table->date('payroll_period_end');
            $table->integer('total_working_days');
            $table->integer('present_days');
            $table->integer('absent_days');
            $table->integer('late_days');
            $table->decimal('total_working_hours', 8, 2);
            $table->decimal('overtime_hours', 8, 2);
            $table->decimal('base_salary', 10, 2);
            $table->decimal('hourly_rate', 8, 2);
            $table->decimal('overtime_rate_multiplier', 4, 2)->default(1.5);
            $table->decimal('calculated_salary', 10, 2);
            $table->decimal('overtime_pay', 10, 2);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->decimal('final_settlement', 10, 2);
            $table->decimal('admin_adjustment_amount', 10, 2)->default(0);
            $table->text('admin_adjustment_note')->nullable();
            $table->enum('status', ['draft', 'reviewed', 'approved', 'paid'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_payrolls');
    }
};
