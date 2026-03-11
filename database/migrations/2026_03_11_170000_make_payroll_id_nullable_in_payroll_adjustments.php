<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            // Drop the existing FK constraint first, then re-add it as nullable
            $table->dropForeign(['payroll_id']);
            $table->unsignedBigInteger('payroll_id')->nullable()->change();
            $table->foreign('payroll_id')
                ->references('id')
                ->on('attendance_payrolls')
                ->onDelete('set null');

            // Add title & month_year columns for standalone adjustments
            if (!Schema::hasColumn('payroll_adjustments', 'title')) {
                $table->string('title', 150)->nullable()->after('code');
            }
            if (!Schema::hasColumn('payroll_adjustments', 'reason')) {
                $table->text('reason')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->dropForeign(['payroll_id']);
            $table->dropColumn(['title', 'reason']);
            $table->unsignedBigInteger('payroll_id')->nullable(false)->change();
            $table->foreign('payroll_id')
                ->references('id')
                ->on('attendance_payrolls')
                ->onDelete('cascade');
        });
    }
};
