<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendance_payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_payrolls', 'month')) {
                $table->unsignedTinyInteger('month')->nullable()->after('employee_id');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'year')) {
                $table->unsignedSmallInteger('year')->nullable()->after('month');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'basic_salary')) {
                $table->decimal('basic_salary', 12, 2)->default(0)->after('base_salary');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'additional_salary')) {
                $table->decimal('additional_salary', 12, 2)->default(0)->after('basic_salary');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'overtime')) {
                $table->decimal('overtime', 12, 2)->default(0)->after('additional_salary');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'satisfactory_sessions')) {
                $table->decimal('satisfactory_sessions', 12, 2)->default(0)->after('overtime');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'treatment_extension_commission')) {
                $table->decimal('treatment_extension_commission', 12, 2)->default(0)->after('satisfactory_sessions');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'satisfaction_bonus')) {
                $table->decimal('satisfaction_bonus', 12, 2)->default(0)->after('treatment_extension_commission');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'assessment_bonus')) {
                $table->decimal('assessment_bonus', 12, 2)->default(0)->after('satisfaction_bonus');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'reference_bonus')) {
                $table->decimal('reference_bonus', 12, 2)->default(0)->after('assessment_bonus');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'personal_patient_commission')) {
                $table->decimal('personal_patient_commission', 12, 2)->default(0)->after('reference_bonus');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'awards_total')) {
                $table->decimal('awards_total', 12, 2)->default(0)->after('personal_patient_commission');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'deductions_total')) {
                $table->decimal('deductions_total', 12, 2)->default(0)->after('awards_total');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'final_salary')) {
                $table->decimal('final_salary', 12, 2)->default(0)->after('deductions_total');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'earnings_breakdown')) {
                $table->json('earnings_breakdown')->nullable()->after('final_salary');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'deductions_breakdown')) {
                $table->json('deductions_breakdown')->nullable()->after('earnings_breakdown');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'awards_breakdown')) {
                $table->json('awards_breakdown')->nullable()->after('deductions_breakdown');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'payslip_data')) {
                $table->json('payslip_data')->nullable()->after('awards_breakdown');
            }

            if (!Schema::hasColumn('attendance_payrolls', 'generated_by')) {
                $table->foreignId('generated_by')->nullable()->after('payment_reference')->constrained('users')->onDelete('set null');
            }

            $table->index(['employee_id', 'month', 'year'], 'attendance_payroll_employee_month_year_idx');
        });

        DB::table('attendance_payrolls')
            ->whereNull('month')
            ->whereNotNull('payroll_period_start')
            ->update([
                'month' => DB::raw('MONTH(payroll_period_start)'),
                'year' => DB::raw('YEAR(payroll_period_start)'),
            ]);

        DB::table('attendance_payrolls')->update([
            'basic_salary' => DB::raw('COALESCE(base_salary, 0)'),
            'overtime' => DB::raw('COALESCE(overtime_pay, 0)'),
            'deductions_total' => DB::raw('COALESCE(deductions, 0)'),
            'awards_total' => DB::raw('COALESCE(bonus, 0)'),
            'final_salary' => DB::raw('COALESCE(final_settlement, 0)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('attendance_payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_payrolls', 'generated_by')) {
                $table->dropConstrainedForeignId('generated_by');
            }

            $table->dropIndex('attendance_payroll_employee_month_year_idx');

            $columns = [
                'month',
                'year',
                'basic_salary',
                'additional_salary',
                'overtime',
                'satisfactory_sessions',
                'treatment_extension_commission',
                'satisfaction_bonus',
                'assessment_bonus',
                'reference_bonus',
                'personal_patient_commission',
                'awards_total',
                'deductions_total',
                'final_salary',
                'earnings_breakdown',
                'deductions_breakdown',
                'awards_breakdown',
                'payslip_data',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('attendance_payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
