<?php

namespace App\Modules\Payroll\Services;

use App\Models\Attendance\AttendancePayroll;
use App\Models\Employee;
use App\Models\User;
use App\Modules\Payroll\Repositories\PayrollRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        private readonly PayrollRepository $repository,
        private readonly PayrollCalculatorService $calculator
    ) {
    }

    public function generateMonthlyPayroll(int $month, int $year, ?int $branchId = null, ?int $employeeId = null, bool $force = false, ?User $generatedBy = null): array
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();

        $employees = $this->repository->getEmployeesForMonth($periodStart, $periodEnd);

        if ($branchId) {
            $employees = $employees->where('branch_id', $branchId)->values();
        }

        if ($employeeId) {
            $employees = $employees->where('id', $employeeId)->values();
        }

        $created = collect();
        $skipped = collect();

        DB::transaction(function () use ($employees, $periodStart, $periodEnd, $month, $year, $force, $generatedBy, &$created, &$skipped) {
            foreach ($employees as $employee) {
                $existing = $this->repository->findMonthlyPayroll($employee->id, $month, $year);

                if ($existing && !$force) {
                    $skipped->push($existing);
                    continue;
                }

                $created->push(
                    $this->calculateAndPersistPayroll($employee, $periodStart, $periodEnd, $generatedBy, $existing)
                );
            }
        });

        return [
            'created' => $created,
            'skipped' => $skipped,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ];
    }

    public function regeneratePayroll(AttendancePayroll $payroll, ?User $generatedBy = null): AttendancePayroll
    {
        $periodStart = Carbon::create($payroll->year, $payroll->month, 1)->startOfMonth();
        $periodEnd = Carbon::create($payroll->year, $payroll->month, 1)->endOfMonth();

        return DB::transaction(function () use ($payroll, $periodStart, $periodEnd, $generatedBy) {
            return $this->calculateAndPersistPayroll($payroll->employee, $periodStart, $periodEnd, $generatedBy, $payroll);
        });
    }

    public function calculateAndPersistPayroll(Employee $employee, Carbon $periodStart, Carbon $periodEnd, ?User $generatedBy = null, ?AttendancePayroll $payroll = null): AttendancePayroll
    {
        $month = (int) $periodStart->month;
        $year = (int) $periodStart->year;

        $payroll = $payroll ?: $this->repository->findMonthlyPayroll($employee->id, $month, $year);

        if (!$payroll) {
            $payroll = new AttendancePayroll();
            $payroll->employee_id = $employee->id;
            $payroll->branch_id = $employee->branch_id;
            $payroll->month = $month;
            $payroll->year = $year;
            $payroll->status = 'draft';
        }

        $adjustments = $payroll->exists
            ? $this->repository->getAdjustmentsForPayroll($payroll)
            : $this->repository->getStandaloneAdjustmentsForEmployee($employee->id, $month, $year);

        $attendanceMetrics = $this->repository->buildAttendanceMetrics($employee, $periodStart, $periodEnd);
        $doctorMetrics = $this->repository->buildDoctorMetrics(
            $this->repository->findDoctorByEmployee($employee),
            $periodStart,
            $periodEnd
        );

        $result = $this->calculator->calculate(
            $employee,
            $periodStart,
            $periodEnd,
            $attendanceMetrics,
            $doctorMetrics,
            $adjustments
        );

        $payroll->fill([
            'payroll_period_start' => $result['period_start'],
            'payroll_period_end' => $result['period_end'],
            'total_working_days' => $result['total_working_days'],
            'present_days' => $result['present_days'],
            'absent_days' => $result['absent_days'],
            'late_days' => $result['late_days'],
            'total_working_hours' => $result['total_working_hours'],
            'overtime_hours' => $result['overtime_hours'],
            'base_salary' => $result['basic_salary'],
            'basic_salary' => $result['basic_salary'],
            'hourly_rate' => $result['total_working_days'] > 0
                ? round($result['basic_salary'] / max($result['total_working_days'] * (float) ($employee->working_hours ?? config('payroll.default_shift_hours', 8)), 1), 2)
                : 0,
            'overtime_rate_multiplier' => (float) config('payroll.overtime_multiplier', 1.5),
            'calculated_salary' => round(collect($result['earnings'])->sum('amount'), 2),
            'overtime_pay' => $result['overtime'],
            'additional_salary' => $result['additional_salary'],
            'overtime' => $result['overtime'],
            'satisfactory_sessions' => $result['satisfactory_sessions'],
            'treatment_extension_commission' => $result['treatment_extension_commission'],
            'satisfaction_bonus' => $result['satisfaction_bonus'],
            'assessment_bonus' => $result['assessment_bonus'],
            'reference_bonus' => $result['reference_bonus'],
            'personal_patient_commission' => $result['personal_patient_commission'],
            'awards_total' => $result['awards_total'],
            'deductions_total' => $result['deductions_total'],
            'deductions' => $result['deductions_total'],
            'bonus' => $result['awards_total'],
            'final_salary' => $result['final_salary'],
            'final_settlement' => $result['final_salary'],
            'earnings_breakdown' => $result['earnings'],
            'deductions_breakdown' => $result['deductions'],
            'awards_breakdown' => $result['awards'],
            'payslip_data' => [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'designation' => $employee->designation,
                ],
                'month' => $month,
                'year' => $year,
                'earnings' => $result['earnings'],
                'awards' => $result['awards'],
                'deductions' => $result['deductions'],
                'final_salary' => $result['final_salary'],
            ],
            'generated_by' => $generatedBy?->id,
        ]);

        $payroll->save();

        // Link any standalone adjustments for this employee/period to the saved payroll
        $this->repository->linkStandaloneAdjustmentsToPayroll($payroll);

        return $payroll->refresh();
    }

    public function addAdjustment(
        AttendancePayroll $payroll,
        string $adjustmentType,
        string $code,
        float $amount,
        ?string $notes = null,
        ?User $createdBy = null,
        array $meta = []
    ): AttendancePayroll {
        $this->repository->addAdjustment([
            'payroll_id' => $payroll->id,
            'employee_id' => $payroll->employee_id,
            'month' => $payroll->month,
            'year' => $payroll->year,
            'adjustment_type' => $adjustmentType,
            'code' => $code,
            'amount' => $amount,
            'notes' => $notes,
            'meta' => $meta ?: null,
            'created_by' => $createdBy?->id,
        ]);

        return $this->regeneratePayroll($payroll, $createdBy);
    }

    public function getPayrollsForIndex(array $filters = [], int $perPage = 50)
    {
        $query = AttendancePayroll::query()->with(['employee', 'branch', 'approver']);

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['period_month'])) {
            $date = Carbon::parse($filters['period_month']);
            $query->where('month', $date->month)->where('year', $date->year);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function monthlyEmployeePayrolls(int $month, int $year): Collection
    {
        return AttendancePayroll::query()
            ->with(['employee', 'branch'])
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('employee_id')
            ->get();
    }

    public function getDashboardStats(int $month, int $year): array
    {
        $query = AttendancePayroll::query()
            ->where('month', $month)
            ->where('year', $year);

        return [
            'total'        => (clone $query)->count(),
            'draft'        => (clone $query)->where('status', 'draft')->count(),
            'approved'     => (clone $query)->where('status', 'approved')->count(),
            'paid'         => (clone $query)->where('status', 'paid')->count(),
            'total_net'    => (clone $query)->sum('final_salary'),
            'total_paid'   => (clone $query)->where('status', 'paid')->sum('final_salary'),
            'pending_amt'  => (clone $query)->whereIn('status', ['draft', 'approved'])->sum('final_salary'),
        ];
    }
}
