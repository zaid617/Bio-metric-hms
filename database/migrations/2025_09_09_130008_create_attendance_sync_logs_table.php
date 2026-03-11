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
        Schema::create('attendance_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('attendance_devices')->onDelete('cascade');
            $table->enum('sync_type', ['users', 'attendance', 'full'])->default('attendance');
            $table->enum('status', ['success', 'failed', 'partial'])->default('success');
            $table->integer('records_fetched')->default(0);
            $table->integer('records_new')->default(0);
            $table->integer('records_duplicate')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sync_logs');
    }
};
