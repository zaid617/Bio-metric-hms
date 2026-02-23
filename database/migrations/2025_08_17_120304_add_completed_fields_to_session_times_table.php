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
      Schema::table('session_times', function (Blueprint $table) {
            $table->unsignedBigInteger('completed_by_doctor_id')->nullable()->after('session_datetime');
            $table->text('work_done')->nullable()->after('completed_by_doctor_id');
            $table->boolean('is_completed')->default(false)->after('work_done');

            $table->foreign('completed_by_doctor_id')
                ->references('id')->on('doctors')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_times', function (Blueprint $table) {
            $table->dropForeign(['completed_by_doctor_id']);
            $table->dropColumn(['completed_by_doctor_id', 'work_done', 'is_completed']);
        });
    }
};
