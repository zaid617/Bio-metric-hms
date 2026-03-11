<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('device_id')->nullable()->after('joining_date');
            $table->string('user_id_on_device')->nullable()->after('device_id');

            $table->foreign('device_id')->references('id')->on('attendance_devices')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropColumn(['device_id', 'user_id_on_device']);
        });
    }
};
