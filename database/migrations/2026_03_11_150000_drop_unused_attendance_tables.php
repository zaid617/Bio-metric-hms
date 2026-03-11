<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop child tables before parents to avoid FK constraint errors
        Schema::dropIfExists('attendance_employee_shifts');
        Schema::dropIfExists('attendance_device_users');
        Schema::dropIfExists('attendance_shifts');
    }

    public function down(): void
    {
        Schema::create('attendance_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_name');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('grace_period_minutes')->default(0);
            $table->integer('auto_checkout_after_hours')->default(9);
            $table->integer('max_working_hours')->default(8);
            $table->timestamps();
        });

        Schema::create('attendance_employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('attendance_shifts')->onDelete('cascade');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_device_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('attendance_devices')->onDelete('cascade');
            $table->integer('uid');
            $table->string('user_id_on_device');
            $table->string('name')->nullable();
            $table->integer('privilege')->default(0);
            $table->string('password')->nullable();
            $table->string('card_number')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->boolean('is_mapped')->default(false);
            $table->string('mapping_confidence')->default('unmatched');
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }
};
