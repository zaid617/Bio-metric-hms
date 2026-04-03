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
    private function money(float|int $value): float
    {
        return round((float) $value, 2);
    }

    public function calculate(
        Employee $employee,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $attendanceMetrics,
        array $doctorMetrics,
        Collection $adjustments
    ): array {
        $totalWorkingDays = (int) ($attendanceMetrics['working_days'] ?? $this->calculateWorkingDays($periodStart, $periodEnd));
        $presentDays = (int) ($attendanceMetrics['present_days'] ?? 0);
        $absentDays = (int) ($attendanceMetrics['absent_days'] ?? 0);
        $leaveDays = (int) ($attendanceMetrics['leave_days'] ?? 0);
        $holidayDays = (int) ($attendanceMetrics['holiday_days'] ?? 0);
        $weekendDays = (int) ($attendanceMetrics['weekend_days'] ?? 0);
        $totalLateCount = (int) ($attendanceMetrics['total_late_count'] ?? $attendanceMetrics['late_days'] ?? 0);
        $totalLateMinutes = (int) ($attendanceMetrics['total_late_minutes'] ?? 0);
        $totalWorkingMinutes = (int) ($attendanceMetrics['total_working_minutes'] ?? 0);
        $overtimeMinutes = max(0, (int) ($attendanceMetrics['overtime_minutes'] ?? 0));
        $overtimeHours = $this->money($overtimeMinutes / 60);

        $baseSalary = $this->money($employee->basic_salary ?? 0);
        $allowanceAlliedHealthCouncil = $this->money($employee->allowance_allied_health_council ?? 0);
        $allowanceHouseJob = $this->money($employee->allowance_house_job ?? 0);
        $allowanceConveyance = $this->money($employee->allowance_conveyance ?? 0);
        $allowanceMedical = $this->money($employee->allowance_medical ?? 0);
        $allowanceHouseRent = $this->money($employee->allowance_house_rent ?? 0);
        $otherAllowance = $this->money($employee->other_allowance ?? 0);

        $incentiveSundayRoster = $this->money($employee->incentive_sunday_roster ?? 0);
        $incentiveHomeVisit = $this->money($employee->incentive_home_visit ?? 0);
        $incentiveSpeechTherapy = $this->money($employee->incentive_speech_therapy ?? 0);
        $incentiveDryNeedling = $this->money($employee->incentive_dry_needling ?? 0);

        $earningAdjustments = $adjustments->where('adjustment_type', 'earning');
        $awardAdjustments = $adjustments->where('adjustment_type', 'award');
        $deductionAdjustments = $adjustments->where('adjustment_type', 'deduction');

        $additionalSalary = $this->money(
            (float) $earningAdjustments
                ->where('code', PayrollEarningType::ADDITIONAL_SALARY)
                ->sum('amount')
        );

        $manualEarningCodes = [
            PayrollEarningType::ADDITIONAL_SALARY,
            PayrollEarningType::OVERTIME,
            PayrollEarningType::BASIC_SALARY,
            PayrollEarningType::ALLOWANCE_ALLIED_HEALTH_COUNCIL,
            PayrollEarningType::ALLOWANCE_HOUSE_JOB,
            PayrollEarningType::ALLOWANCE_CONVEYANCE,
            PayrollEarningType::ALLOWANCE_MEDICAL,
            PayrollEarningType::ALLOWANCE_HOUSE_RENT,
            PayrollEarningType::OTHER_ALLOWANCE,
            PayrollEarningType::INCENTIVE_SUNDAY_ROSTER,
            PayrollEarningType::INCENTIVE_HOME_VISIT,
            PayrollEarningType::INCENTIVE_SPEECH_THERAPY,
            PayrollEarningType::INCENTIVE_DRY_NEEDLING,
        ];

        $manualAdditionalEarnings = $this->money(
            (float) $earningAdjustments
                ->reject(fn ($adjustment) => in_array($adjustment->code, $manualEarningCodes, true))
                ->sum('amount')
        );

        $dailyRate = $totalWorkingDays > 0
            ? ($baseSalary / $totalWorkingDays)
            : 0.0;
        $absentDeduction = $this->money($absentDays * $dailyRate);
        $lateDeduction = $this->money(floor($totalLateCount / 3) * ($dailyRate / 2));

        $sumDeductionCodes = static function (Collection $items, array $codes): float {
            return (float) $items
                ->filter(fn ($adjustment) => in_array(strtoupper((string) $adjustment->code), $codes, true))
                ->sum('amount');
        };

        $tax = $this->money($sumDeductionCodes($deductionAdjustments, [PayrollDeductionType::TAX]));
        $providentFund = $this->money($sumDeductionCodes($deductionAdjustments, [PayrollDeductionType::PROVIDENT_FUND]));
        $eobi = $this->money($sumDeductionCodes($deductionAdjustments, [PayrollDeductionType::EOBI]));
        $advance = $this->money($sumDeductionCodes($deductionAdjustments, [PayrollDeductionType::ADVANCE, PayrollDeductionType::ADVANCE_SALARY_DEDUCTION]));
        $loan = $this->money($sumDeductionCodes($deductionAdjustments, [PayrollDeductionType::LOAN]));

        $excludedOtherDeductionCodes = [
            PayrollDeductionType::TAX,
            PayrollDeductionType::PROVIDENT_FUND,
            PayrollDeductionType::EOBI,
            PayrollDeductionType::ADVANCE,
            PayrollDeductionType::ADVANCE_SALARY_DEDUCTION,
            PayrollDeductionType::LOAN,
            PayrollDeductionType::ABSENT,
            PayrollDeductionType::ABSENT_DEDUCTION,
            PayrollDeductionType::LATE_COMING,
            PayrollDeductionType::LATE_DEDUCTION,
        ];

        $otherDeductionItems = $deductionAdjustments
            ->filter(fn ($adjustment) => !in_array(strtoupper((string) $adjustment->code), $excludedOtherDeductionCodes, true))
            ->map(function ($adjustment) {
                return [
                    'type' => $adjustment->code ?: PayrollDeductionType::OTHER_DEDUCTION,
                    'amount' => $this->money((float) $adjustment->amount),
                    'notes' => $adjustment->notes,
                ];
            })
            ->values();

        $otherDeduction = $this->money((float) $otherDeductionItems->sum('amount'));

        $awards = [];
        $fullyPresent = $presentDays >= $totalWorkingDays && $totalWorkingDays > 0;
        if ($totalLateCount === 0 && $absentDays === 0 && $fullyPresent) {
            $awards[] = [
                'type' => PayrollAwardType::PUNCTUALITY_AWARD,
                'amount' => $this->money((float) config('payroll.awards.punctuality_amount', 2000)),
                'notes' => 'All payable working days attended with zero late arrivals',
            ];
        }

        foreach ($awardAdjustments as $adjustment) {
            $awards[] = [
                'type' => $adjustment->code ?: PayrollAwardType::CUSTOM,
                'amount' => $this->money((float) $adjustment->amount),
                'notes' => $adjustment->notes,
            ];
        }

        $earnings = [
            ['type' => PayrollEarningType::BASIC_SALARY, 'amount' => $baseSalary, 'notes' => null],
            ['type' => PayrollEarningType::ALLOWANCE_ALLIED_HEALTH_COUNCIL, 'amount' => $allowanceAlliedHealthCouncil, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::ALLOWANCE_HOUSE_JOB, 'amount' => $allowanceHouseJob, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::ALLOWANCE_CONVEYANCE, 'amount' => $allowanceConveyance, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::ALLOWANCE_MEDICAL, 'amount' => $allowanceMedical, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::ALLOWANCE_HOUSE_RENT, 'amount' => $allowanceHouseRent, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::INCENTIVE_SUNDAY_ROSTER, 'amount' => $incentiveSundayRoster, 'notes' => 'Employee profile incentive'],
            ['type' => PayrollEarningType::INCENTIVE_HOME_VISIT, 'amount' => $incentiveHomeVisit, 'notes' => 'Employee profile incentive'],
            ['type' => PayrollEarningType::INCENTIVE_SPEECH_THERAPY, 'amount' => $incentiveSpeechTherapy, 'notes' => 'Employee profile incentive'],
            ['type' => PayrollEarningType::INCENTIVE_DRY_NEEDLING, 'amount' => $incentiveDryNeedling, 'notes' => 'Employee profile incentive'],
            ['type' => PayrollEarningType::OTHER_ALLOWANCE, 'amount' => $otherAllowance, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::ADDITIONAL_SALARY, 'amount' => $additionalSalary, 'notes' => 'Manual/admin additional salary'],
            ['type' => PayrollEarningType::CUSTOM, 'amount' => $manualAdditionalEarnings, 'notes' => 'Other earning adjustments'],
        ];

        $deductions = [
            ['type' => PayrollDeductionType::TAX, 'amount' => $tax, 'notes' => 'Tax deduction'],
            ['type' => PayrollDeductionType::PROVIDENT_FUND, 'amount' => $providentFund, 'notes' => 'Provident fund deduction'],
            ['type' => PayrollDeductionType::EOBI, 'amount' => $eobi, 'notes' => 'EOBI deduction'],
            ['type' => PayrollDeductionType::ADVANCE, 'amount' => $advance, 'notes' => 'Advance deduction'],
            ['type' => PayrollDeductionType::LOAN, 'amount' => $loan, 'notes' => 'Loan recovery'],
            ['type' => PayrollDeductionType::ABSENT_DEDUCTION, 'amount' => $absentDeduction, 'notes' => 'Absent deduction = absent days × (basic / working days)'],
            ['type' => PayrollDeductionType::LATE_DEDUCTION, 'amount' => $lateDeduction, 'notes' => 'Late deduction = floor(late count / 3) × (basic / working days / 2)'],
            ['type' => PayrollDeductionType::OTHER_DEDUCTION, 'amount' => $otherDeduction, 'notes' => 'Other deduction adjustments'],
        ];

        foreach ($otherDeductionItems as $otherDeductionItem) {
            $deductions[] = $otherDeductionItem;
        }

        $earningsTotal = $this->money((float) collect($earnings)->sum('amount'));
        $awardsTotal = $this->money((float) collect($awards)->sum('amount'));
        $deductionsTotal = $this->money((float) collect($deductions)->sum('amount'));

        $finalSalary = $this->money(($earningsTotal + $awardsTotal) - $deductionsTotal);
        $warnings = [];
        if (!empty($attendanceMetrics['no_attendance_warning'])) {
            $warnings[] = 'No attendance records found for this period. Please verify attendance sync and approvals.';
        }
        if ($totalWorkingDays <= 0) {
            $warnings[] = 'No payable working days found for the selected period.';
        }

        return [
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'month' => (int) $periodStart->month,
            'year' => (int) $periodStart->year,
            'total_working_days' => $totalWorkingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'leave_days' => $leaveDays,
            'holiday_days' => $holidayDays,
            'weekend_days' => $weekendDays,
            'late_days' => $totalLateCount,
            'total_late_count' => $totalLateCount,
            'total_late_minutes' => $totalLateMinutes,
            'total_working_hours' => $this->money($totalWorkingMinutes / 60),
            'overtime_hours' => $overtimeHours,
            'total_overtime_hours' => $overtimeHours,
            'basic_salary' => $baseSalary,
            'additional_salary' => $additionalSalary,
            'allowance_allied_health_council' => $allowanceAlliedHealthCouncil,
            'allowance_house_job' => $allowanceHouseJob,
            'allowance_conveyance' => $allowanceConveyance,
            'allowance_medical' => $allowanceMedical,
            'allowance_house_rent' => $allowanceHouseRent,
            'other_allowance' => $otherAllowance,
            'incentive_sunday_roster' => $incentiveSundayRoster,
            'incentive_home_visit' => $incentiveHomeVisit,
            'incentive_speech_therapy' => $incentiveSpeechTherapy,
            'incentive_dry_needling' => $incentiveDryNeedling,
            'overtime' => 0.0,
            'satisfactory_sessions' => 0.0,
            'treatment_extension_commission' => 0.0,
            'satisfaction_bonus' => 0.0,
            'assessment_bonus' => 0.0,
            'reference_bonus' => 0.0,
            'personal_patient_commission' => 0.0,
            'awards_total' => $awardsTotal,
            'deductions_total' => $deductionsTotal,
            'absent_deduction' => $absentDeduction,
            'late_deduction' => $lateDeduction,
            'tax' => $tax,
            'provident_fund' => $providentFund,
            'eobi' => $eobi,
            'advance' => $advance,
            'loan' => $loan,
            'other_deduction' => $otherDeduction,
            'final_salary' => $finalSalary,
            'warnings' => $warnings,
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
