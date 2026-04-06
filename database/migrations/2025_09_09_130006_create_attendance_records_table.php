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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained('attendance_devices')->onDelete('set null');
            $table->date('attendance_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->integer('total_working_minutes')->nullable();
            $table->integer('overtime_minutes')->default(0);
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'leave', 'holiday', 'weekend'])->default('present');
            $table->boolean('is_checkout_missing')->default(false);
            $table->boolean('auto_checkout_applied')->default(false);
            $table->time('auto_checkout_time')->nullable();
            $table->text('admin_note')->nullable();
            $table->boolean('is_manually_adjusted')->default(false);
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('adjusted_at')->nullable();
            $table->timestamps();

            // Unique constraint: one record per employee per date
            $table->unique(['employee_id', 'attendance_date'], 'unique_employee_attendance_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
