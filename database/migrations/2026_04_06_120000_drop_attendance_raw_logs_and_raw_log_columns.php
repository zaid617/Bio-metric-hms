<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_records')) {
            $hasCheckInRawLogId = Schema::hasColumn('attendance_records', 'check_in_raw_log_id');
            $hasCheckOutRawLogId = Schema::hasColumn('attendance_records', 'check_out_raw_log_id');

            if ($hasCheckInRawLogId || $hasCheckOutRawLogId) {
                Schema::table('attendance_records', function (Blueprint $table) use ($hasCheckInRawLogId, $hasCheckOutRawLogId) {
                    if ($hasCheckInRawLogId) {
                        try {
                            $table->dropForeign(['check_in_raw_log_id']);
                        } catch (\Throwable $e) {
                            // Foreign key may already be absent in some environments.
                        }

                        $table->dropColumn('check_in_raw_log_id');
                    }

                    if ($hasCheckOutRawLogId) {
                        try {
                            $table->dropForeign(['check_out_raw_log_id']);
                        } catch (\Throwable $e) {
                            // Foreign key may already be absent in some environments.
                        }

                        $table->dropColumn('check_out_raw_log_id');
                    }
                });
            }
        }

        if (Schema::hasTable('attendance_raw_logs')) {
            Schema::drop('attendance_raw_logs');
        }
    }

    public function down(): void
    {
        // Intentionally left blank: raw-log schema rollback is no longer supported.
    }
};
