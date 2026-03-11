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
        Schema::create('attendance_raw_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('attendance_devices')->onDelete('cascade');
            $table->integer('device_user_uid');
            $table->string('user_id_on_device');
            $table->dateTime('punch_time');
            $table->integer('punch_type')->nullable(); // raw from device
            $table->integer('verify_type')->nullable();
            $table->integer('work_code')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate logs
            $table->unique(['device_id', 'user_id_on_device', 'punch_time'], 'unique_attendance_log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_raw_logs');
    }
};
