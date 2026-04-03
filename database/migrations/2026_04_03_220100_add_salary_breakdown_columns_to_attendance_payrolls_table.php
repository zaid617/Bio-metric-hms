<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_payrolls', 'total_late_count')) {
                $table->unsignedInteger('total_late_count')->default(0)->after('late_days');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'total_late_minutes')) {
                $table->unsignedInteger('total_late_minutes')->default(0)->after('total_late_count');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'late_deduction')) {
                $table->decimal('late_deduction', 12, 2)->default(0)->after('deductions_total');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'total_overtime_hours')) {
                $table->decimal('total_overtime_hours', 8, 2)->default(0)->after('overtime_hours');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'leave_days')) {
                $table->unsignedInteger('leave_days')->default(0)->after('absent_days');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'holiday_days')) {
                $table->unsignedInteger('holiday_days')->default(0)->after('leave_days');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'weekend_days')) {
                $table->unsignedInteger('weekend_days')->default(0)->after('holiday_days');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'absent_deduction')) {
                $table->decimal('absent_deduction', 12, 2)->default(0)->after('late_deduction');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'tax')) {
                $table->decimal('tax', 12, 2)->default(0)->after('absent_deduction');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'provident_fund')) {
                $table->decimal('provident_fund', 12, 2)->default(0)->after('tax');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'eobi')) {
                $table->decimal('eobi', 12, 2)->default(0)->after('provident_fund');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'advance')) {
                $table->decimal('advance', 12, 2)->default(0)->after('eobi');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'loan')) {
                $table->decimal('loan', 12, 2)->default(0)->after('advance');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'other_deduction')) {
                $table->decimal('other_deduction', 12, 2)->default(0)->after('loan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_payrolls', function (Blueprint $table) {
            $columns = [
                'total_late_count',
                'total_late_minutes',
                'late_deduction',
                'total_overtime_hours',
                'leave_days',
                'holiday_days',
                'weekend_days',
                'absent_deduction',
                'tax',
                'provident_fund',
                'eobi',
                'advance',
                'loan',
                'other_deduction',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('attendance_payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
