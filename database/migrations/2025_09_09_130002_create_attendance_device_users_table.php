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
        Schema::create('attendance_device_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('attendance_devices')->onDelete('cascade');
            $table->integer('uid'); // device internal UID
            $table->string('user_id_on_device'); // the ID stored on device
            $table->string('name')->nullable(); // as stored on device
            $table->integer('privilege')->default(0); // 0=user, 14=admin
            $table->string('password')->nullable(); // device password
            $table->string('card_number')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->boolean('is_mapped')->default(false);
            $table->enum('mapping_confidence', ['auto', 'manual', 'unmatched'])->default('unmatched');
            $table->json('raw_data')->nullable(); // full raw data from device
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_device_users');
    }
};
