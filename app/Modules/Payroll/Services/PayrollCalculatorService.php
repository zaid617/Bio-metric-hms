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
        $allowanceBranchManager = $this->money($employee->allowance_branch_manager ?? 0);
        $allowanceAssistantBranchManager = $this->money($employee->allowance_assistant_branch_manager ?? 0);

        $profileOtherAllowances = $this->normalizeOtherAllowances($employee->other_allowances ?? null);
        $profileOtherAllowancesTotal = $this->money((float) collect($profileOtherAllowances)->sum('amount'));
        $legacyOtherAllowance = $this->money($employee->other_allowance ?? 0);
        $otherAllowanceDifference = $this->money(max(0, $legacyOtherAllowance - $profileOtherAllowancesTotal));
        $otherAllowance = $this->money($profileOtherAllowancesTotal + $otherAllowanceDifference);

        $incentiveSundayRoster = $this->money($employee->incentive_sunday_roster ?? 0);
        $incentiveHomeVisit = $this->money($employee->incentive_home_visit ?? 0);
        $incentiveSpeechTherapy = $this->money($employee->incentive_speech_therapy ?? 0);
        $incentiveDryNeedling = $this->money($employee->incentive_dry_needling ?? 0);

        $earningAdjustments = $adjustments
            ->where('adjustment_type', 'earning')
            ->values();
        $awardAdjustments = $adjustments
            ->where('adjustment_type', 'award')
            ->values();
        $deductionAdjustments = $adjustments
            ->where('adjustment_type', 'deduction')
            ->values();

        $leaveAdjustments = $deductionAdjustments
            ->filter(function ($adjustment) {
                $code = strtoupper(trim((string) data_get($adjustment, 'code', '')));
                return in_array($code, [PayrollDeductionType::PAID_LEAVE, PayrollDeductionType::UNPAID_LEAVE], true);
            })
            ->values();

        $leaveEntries = $leaveAdjustments
            ->map(function ($adjustment): array {
                $code = strtoupper(trim((string) data_get($adjustment, 'code', PayrollDeductionType::PAID_LEAVE)));
                $meta = data_get($adjustment, 'meta');
                $days = (float) data_get($meta, 'days', 0);
                $reason = trim((string) data_get($meta, 'reason', data_get($adjustment, 'notes', '')));

                if ($days <= 0) {
                    return [];
                }

                return [
                    'leave_type' => $code === PayrollDeductionType::UNPAID_LEAVE ? 'unpaid' : 'paid',
                    'days' => $this->money($days),
                    'reason' => $reason,
                ];
            })
            ->filter(fn (array $entry) => !empty($entry))
            ->values();

        $paidLeaveDays = $this->money((float) $leaveEntries
            ->where('leave_type', 'paid')
            ->sum('days'));
        $unpaidLeaveDays = $this->money((float) $leaveEntries
            ->where('leave_type', 'unpaid')
            ->sum('days'));

        $leaveDays = (int) round($leaveDays + $paidLeaveDays + $unpaidLeaveDays);

        $deductionAdjustments = $deductionAdjustments
            ->reject(function ($adjustment) {
                $code = strtoupper(trim((string) data_get($adjustment, 'code', '')));
                return in_array($code, [PayrollDeductionType::PAID_LEAVE, PayrollDeductionType::UNPAID_LEAVE], true);
            })
            ->values();

        $normalizeCode = static function ($value, string $fallback): string {
            $normalized = strtoupper(trim((string) $value));
            return $normalized !== '' ? $normalized : $fallback;
        };

        $sumByCode = static function (Collection $items, array $codes): float {
            return (float) $items
                ->filter(function ($adjustment) use ($codes) {
                    $code = strtoupper(trim((string) data_get($adjustment, 'code', '')));
                    return in_array($code, $codes, true);
                })
                ->sum('amount');
        };

        $additionalSalary = $this->money(
            $sumByCode($earningAdjustments, [PayrollEarningType::ADDITIONAL_SALARY])
        );

        $absentPerDay = 500.0;
        $latePerDay = 200.0;

        try {
            $absentPerDay = (float) config('payroll.deductions.absent_per_day', config('payroll.absent_per_day', 500));
            $latePerDay = (float) config('payroll.deductions.late_per_day', config('payroll.late_per_day', 200));
        } catch (\Throwable) {
            // Keep defaults for unit contexts where Laravel config container is not bootstrapped.
        }

        $absentPerDay = $this->money($absentPerDay);
        $latePerDay = $this->money($latePerDay);
        $effectiveAbsentDays = max(0, $absentDays - (int) floor($paidLeaveDays + $unpaidLeaveDays));
        $absentDeduction = $this->money($effectiveAbsentDays * $absentPerDay);
        $unpaidLeaveDeduction = $this->money($unpaidLeaveDays * $absentPerDay);
        $lateDeduction = $this->money($totalLateCount * $latePerDay);

        $tax = $this->money($sumByCode($deductionAdjustments, [PayrollDeductionType::TAX]));
        $providentFund = $this->money($sumByCode($deductionAdjustments, [PayrollDeductionType::PROVIDENT_FUND]));
        $eobi = $this->money($sumByCode($deductionAdjustments, [PayrollDeductionType::EOBI]));
        $advance = $this->money($sumByCode($deductionAdjustments, [PayrollDeductionType::ADVANCE, PayrollDeductionType::ADVANCE_SALARY_DEDUCTION]));
        $loan = $this->money($sumByCode($deductionAdjustments, [PayrollDeductionType::LOAN]));

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
            PayrollDeductionType::PAID_LEAVE,
            PayrollDeductionType::UNPAID_LEAVE,
        ];

        $otherDeduction = $this->money(
            (float) $deductionAdjustments
                ->reject(function ($adjustment) use ($normalizeCode, $excludedOtherDeductionCodes) {
                    $code = $normalizeCode(data_get($adjustment, 'code'), PayrollDeductionType::CUSTOM);
                    return in_array($code, $excludedOtherDeductionCodes, true);
                })
                ->sum('amount')
        );

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
                'type' => $normalizeCode(data_get($adjustment, 'code'), PayrollAwardType::CUSTOM),
                'amount' => $this->money((float) data_get($adjustment, 'amount', 0)),
                'notes' => data_get($adjustment, 'notes') ?? data_get($adjustment, 'reason'),
            ];
        }

        $earnings = [
            ['type' => PayrollEarningType::BASIC_SALARY, 'amount' => $baseSalary, 'notes' => 'Employee base salary'],
            ['type' => PayrollEarningType::ALLOWANCE_ALLIED_HEALTH_COUNCIL, 'amount' => $allowanceAlliedHealthCouncil, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::ALLOWANCE_HOUSE_JOB, 'amount' => $allowanceHouseJob, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::ALLOWANCE_CONVEYANCE, 'amount' => $allowanceConveyance, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::ALLOWANCE_MEDICAL, 'amount' => $allowanceMedical, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::ALLOWANCE_HOUSE_RENT, 'amount' => $allowanceHouseRent, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::ALLOWANCE_BRANCH_MANAGER, 'amount' => $allowanceBranchManager, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::ALLOWANCE_ASSISTANT_BRANCH_MANAGER, 'amount' => $allowanceAssistantBranchManager, 'notes' => 'Employee profile allowance'],
            ['type' => PayrollEarningType::INCENTIVE_SUNDAY_ROSTER, 'amount' => $incentiveSundayRoster, 'notes' => 'Employee profile incentive'],
            ['type' => PayrollEarningType::INCENTIVE_HOME_VISIT, 'amount' => $incentiveHomeVisit, 'notes' => 'Employee profile incentive'],
            ['type' => PayrollEarningType::INCENTIVE_SPEECH_THERAPY, 'amount' => $incentiveSpeechTherapy, 'notes' => 'Employee profile incentive'],
            ['type' => PayrollEarningType::INCENTIVE_DRY_NEEDLING, 'amount' => $incentiveDryNeedling, 'notes' => 'Employee profile incentive'],
        ];

        foreach ($profileOtherAllowances as $allowance) {
            $earnings[] = [
                'type' => PayrollEarningType::OTHER_ALLOWANCE,
                'amount' => $this->money((float) ($allowance['amount'] ?? 0)),
                'label' => (string) ($allowance['label'] ?? 'Other Allowance'),
                'notes' => 'Employee profile allowance',
            ];
        }

        if ($otherAllowanceDifference > 0) {
            $earnings[] = [
                'type' => PayrollEarningType::OTHER_ALLOWANCE,
                'amount' => $otherAllowanceDifference,
                'label' => trim((string) ($employee->other_allowance_label ?? '')) ?: 'Other Allowance',
                'notes' => 'Employee profile allowance',
            ];
        }

        foreach ($earningAdjustments as $adjustment) {
            $code = $normalizeCode(data_get($adjustment, 'code'), PayrollEarningType::CUSTOM);
            if ($code === PayrollEarningType::OVERTIME) {
                continue;
            }

            $earnings[] = [
                'type' => $code,
                'amount' => $this->money((float) data_get($adjustment, 'amount', 0)),
                'notes' => data_get($adjustment, 'notes') ?? data_get($adjustment, 'reason') ?? 'Adjustment',
            ];
        }

        $deductions = [
            [
                'type' => PayrollDeductionType::ABSENT_DEDUCTION,
                'amount' => $absentDeduction,
                'notes' => 'Absent deduction = absent days × absent deduction/day setting',
            ],
            [
                'type' => PayrollDeductionType::UNPAID_LEAVE,
                'amount' => $unpaidLeaveDeduction,
                'notes' => 'Unpaid leave deduction = unpaid leave days × absent deduction/day setting',
            ],
            [
                'type' => PayrollDeductionType::LATE_DEDUCTION,
                'amount' => $lateDeduction,
                'notes' => 'Late deduction = late count × late deduction/day setting',
            ],
        ];

        foreach ($deductionAdjustments as $adjustment) {
            $deductions[] = [
                'type' => $normalizeCode(data_get($adjustment, 'code'), PayrollDeductionType::CUSTOM),
                'amount' => $this->money((float) data_get($adjustment, 'amount', 0)),
                'notes' => data_get($adjustment, 'notes') ?? data_get($adjustment, 'reason') ?? 'Adjustment',
            ];
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
            'absent_days' => $effectiveAbsentDays,
            'leave_days' => $leaveDays,
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
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
            'allowance_branch_manager' => $allowanceBranchManager,
            'allowance_assistant_branch_manager' => $allowanceAssistantBranchManager,
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
            'unpaid_leave_deduction' => $unpaidLeaveDeduction,
            'late_deduction' => $lateDeduction,
            'tax' => $tax,
            'provident_fund' => $providentFund,
            'eobi' => $eobi,
            'advance' => $advance,
            'loan' => $loan,
            'other_deduction' => $otherDeduction,
            'final_salary' => $finalSalary,
            'warnings' => $warnings,
            'leave_entries' => $leaveEntries->all(),
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

    private function normalizeOtherAllowances(array|string|null $rawAllowances): array
    {
        if (is_string($rawAllowances)) {
            $decoded = json_decode($rawAllowances, true);
            $rawAllowances = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($rawAllowances)) {
            return [];
        }

        return collect($rawAllowances)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): array {
                $amount = $this->money((float) ($row['amount'] ?? 0));
                $label = trim((string) ($row['label'] ?? ''));

                return [
                    'label' => $label !== '' ? $label : 'Other Allowance',
                    'amount' => $amount,
                ];
            })
            ->filter(fn (array $row) => $row['amount'] > 0)
            ->values()
            ->all();
    }
}
