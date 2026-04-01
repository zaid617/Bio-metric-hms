<?php

namespace App\Modules\Payroll\Services;

use App\Models\Employee;
use App\Modules\Payroll\Types\PayrollAwardType;
use App\Modules\Payroll\Types\PayrollDeductionType;
use App\Modules\Payroll\Types\PayrollEarningType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class PayrollCalculatorService
{
    public function calculate(
        Employee $employee,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $attendanceMetrics,
        array $doctorMetrics,
        Collection $adjustments
    ): array {
        $totalWorkingDays = $this->calculateWorkingDays($periodStart, $periodEnd);
        $baseSalary = (float) ($employee->basic_salary ?? 0);
        $additionalSalary = (float) $adjustments
            ->where('adjustment_type', 'earning')
            ->where('code', PayrollEarningType::ADDITIONAL_SALARY)
            ->sum('amount');

        $shiftHours = (float) ($employee->working_hours ?? config('payroll.default_shift_hours', 8));
        $hourlyRate = $totalWorkingDays > 0 ? $baseSalary / max($totalWorkingDays * $shiftHours, 1) : 0.0;
        $overtimeHours = ((int) $attendanceMetrics['overtime_minutes']) / 60;
        $overtime = $overtimeHours * $hourlyRate * (float) config('payroll.overtime_multiplier', 1.5);

        $satisfactorySessions = (int) $doctorMetrics['satisfactory_sessions_count'] * (float) config('payroll.bonuses.satisfactory_session_amount', 300);
        $treatmentExtensionCommission = (float) $doctorMetrics['treatment_extension_revenue'] * (float) config('payroll.rates.treatment_extension_commission', 0.10);
        $satisfactionBonus = (int) $doctorMetrics['high_satisfaction_count'] * (float) config('payroll.bonuses.satisfaction_bonus_per_feedback', 200);
        $assessmentBonus = (float) $doctorMetrics['assessment_revenue'] * (float) config('payroll.rates.assessment_incentive', 0.05);
        $referenceBonus = (int) $doctorMetrics['reference_count'] * (float) config('payroll.bonuses.reference_bonus_per_patient', 500);
        $personalPatientCommission = (float) $doctorMetrics['personal_patient_revenue'] * (float) config('payroll.rates.personal_patient_commission', 0.20);

        $awards = [];
        // Punctuality award: employee must be present on ALL working days and have zero late arrivals.
        // absent_days is already computed as (working_days − present_days), so absent_days == 0
        // guarantees full attendance; we also verify present_days explicitly as a safety net.
        $fullyPresent = (int) $attendanceMetrics['present_days'] >= $totalWorkingDays
            && $totalWorkingDays > 0;
        if ((int) $attendanceMetrics['late_days'] === 0
            && (int) $attendanceMetrics['absent_days'] === 0
            && $fullyPresent) {
            $awards[] = [
                'type'   => PayrollAwardType::PUNCTUALITY_AWARD,
                'amount' => (float) config('payroll.awards.punctuality_amount', 2000),
                'notes'  => 'All working days attended, zero late arrivals',
            ];
        }

        foreach ($adjustments->where('adjustment_type', 'award') as $adjustment) {
            $awards[] = [
                'type' => $adjustment->code ?: PayrollAwardType::CUSTOM,
                'amount' => (float) $adjustment->amount,
                'notes' => $adjustment->notes,
            ];
        }

        $deductions = [
            [
                'type' => PayrollDeductionType::ABSENT,
                'amount' => (int) $attendanceMetrics['absent_days'] * (float) config('payroll.deductions.absent_per_day', 500),
                'notes' => 'Automated attendance deduction',
            ],
            [
                'type' => PayrollDeductionType::LATE_COMING,
                'amount' => (int) $attendanceMetrics['late_days'] * (float) config('payroll.deductions.late_per_day', 200),
                'notes' => 'Automated attendance deduction',
            ],
        ];

        foreach ($adjustments->where('adjustment_type', 'deduction') as $adjustment) {
            $deductions[] = [
                'type' => $adjustment->code ?: PayrollDeductionType::CUSTOM,
                'amount' => (float) $adjustment->amount,
                'notes' => $adjustment->notes,
            ];
        }

        $earnings = [
            ['type' => PayrollEarningType::BASIC_SALARY, 'amount' => $baseSalary, 'notes' => null],
            ['type' => PayrollEarningType::ADDITIONAL_SALARY, 'amount' => $additionalSalary, 'notes' => 'Manual/admin additional salary'],
            ['type' => PayrollEarningType::OVERTIME, 'amount' => $overtime, 'notes' => 'Attendance overtime'],
            ['type' => PayrollEarningType::SATISFACTORY_SESSIONS, 'amount' => $satisfactorySessions, 'notes' => null],
            ['type' => PayrollEarningType::TREATMENT_EXTENSION_COMMISSION, 'amount' => $treatmentExtensionCommission, 'notes' => '10% commission'],
            ['type' => PayrollEarningType::SATISFACTION_BONUS, 'amount' => $satisfactionBonus, 'notes' => 'Satisfaction >= threshold'],
            ['type' => PayrollEarningType::ASSESSMENT_BONUS, 'amount' => $assessmentBonus, 'notes' => '5% assessment incentive'],
            ['type' => PayrollEarningType::REFERENCE_BONUS, 'amount' => $referenceBonus, 'notes' => 'Referred patient bonus'],
            ['type' => PayrollEarningType::PERSONAL_PATIENT_COMMISSION, 'amount' => $personalPatientCommission, 'notes' => '20% personal patient commission'],
        ];

        foreach ($adjustments->where('adjustment_type', 'earning') as $adjustment) {
            if (in_array($adjustment->code, [PayrollEarningType::ADDITIONAL_SALARY, PayrollEarningType::OVERTIME], true)) {
                continue;
            }

            $earnings[] = [
                'type' => $adjustment->code ?: PayrollEarningType::CUSTOM,
                'amount' => (float) $adjustment->amount,
                'notes' => $adjustment->notes,
            ];
        }

        $earningsTotal = collect($earnings)->sum('amount');
        $awardsTotal = collect($awards)->sum('amount');
        $deductionsTotal = collect($deductions)->sum('amount');

        $finalSalary = ($earningsTotal + $awardsTotal) - $deductionsTotal;

        return [
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'month' => (int) $periodStart->month,
            'year' => (int) $periodStart->year,
            'total_working_days' => $totalWorkingDays,
            'present_days' => (int) $attendanceMetrics['present_days'],
            'absent_days' => (int) $attendanceMetrics['absent_days'],
            'late_days' => (int) $attendanceMetrics['late_days'],
            'total_working_hours' => round(((int) $attendanceMetrics['total_working_minutes']) / 60, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'basic_salary' => round($baseSalary, 2),
            'additional_salary' => round($additionalSalary, 2),
            'overtime' => round($overtime, 2),
            'satisfactory_sessions' => round($satisfactorySessions, 2),
            'treatment_extension_commission' => round($treatmentExtensionCommission, 2),
            'satisfaction_bonus' => round($satisfactionBonus, 2),
            'assessment_bonus' => round($assessmentBonus, 2),
            'reference_bonus' => round($referenceBonus, 2),
            'personal_patient_commission' => round($personalPatientCommission, 2),
            'awards_total' => round($awardsTotal, 2),
            'deductions_total' => round($deductionsTotal, 2),
            'final_salary' => round($finalSalary, 2),
            'earnings' => $earnings,
            'awards' => $awards,
            'deductions' => $deductions,
        ];
    }

    private function calculateWorkingDays(Carbon $periodStart, Carbon $periodEnd): int
    {
        // Determine which days of the week are off.
        // dayOfWeek: 0 = Sunday, 6 = Saturday.
        $offDays = config('payroll.work_on_saturday', true) ? [0] : [0, 6];

        $workingDays = 0;
        foreach (CarbonPeriod::create($periodStart, $periodEnd) as $date) {
            if (!in_array($date->dayOfWeek, $offDays, true)) {
                $workingDays++;
            }
        }

        return $workingDays;
    }
}
