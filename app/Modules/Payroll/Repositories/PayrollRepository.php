<?php

namespace App\Modules\Payroll\Repositories;

use App\Models\Attendance\AttendancePayroll;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Checkup;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\TreatmentSession;
use App\Modules\Payroll\Models\PayrollAdjustment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollRepository
{
    private ?Collection $doctorNameMap = null;

    public function getEmployeesForMonth(Carbon $periodStart, Carbon $periodEnd): EloquentCollection
    {
        $query = Employee::query()->with('branch');

        if (DB::getSchemaBuilder()->hasColumn('employees', 'joining_date')) {
            $query->where(function ($q) use ($periodEnd) {
                $q->whereNull('joining_date')->orWhereDate('joining_date', '<=', $periodEnd->toDateString());
            });
        }

        if (DB::getSchemaBuilder()->hasColumn('employees', 'status')) {
            $query->where('status', 'active');
        }

        return $query->get();
    }

    public function findMonthlyPayroll(int $employeeId, int $month, int $year): ?AttendancePayroll
    {
        return AttendancePayroll::query()
            ->where('employee_id', $employeeId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();
    }

    public function buildAttendanceMetrics(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
    {
        $records = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get()
            ->keyBy(function (AttendanceRecord $record) {
                return $record->attendance_date instanceof Carbon
                    ? $record->attendance_date->toDateString()
                    : (string) $record->attendance_date;
            });

        // Determine off-days: Sunday always off; Saturday off only if work_on_saturday = false
        $offDays = config('payroll.work_on_saturday', true) ? [0] : [0, 6];

        $effectiveStart = $periodStart->copy();
        if (!empty($employee->joining_date)) {
            $joiningDate = Carbon::parse($employee->joining_date);
            if ($joiningDate->betweenIncluded($periodStart, $periodEnd) && $joiningDate->gt($effectiveStart)) {
                $effectiveStart = $joiningDate->copy();
            }
        }

        if ($effectiveStart->gt($periodEnd)) {
            return [
                'working_days' => 0,
                'present_days' => 0,
                'absent_days' => 0,
                'leave_days' => 0,
                'holiday_days' => 0,
                'weekend_days' => 0,
                'late_days' => 0,
                'total_late_count' => 0,
                'total_late_minutes' => 0,
                'total_working_minutes' => 0,
                'overtime_minutes' => 0,
                'no_attendance_warning' => true,
            ];
        }

        $workingDays = 0;
        $presentDays = 0;
        $absentDays = 0;
        $leaveDays = 0;
        $holidayDays = 0;
        $weekendDays = 0;
        $totalLateCount = 0;
        $totalLateMinutes = 0;
        $totalWorkingMinutes = 0;
        $overtimeMinutes = 0;

        foreach (CarbonPeriod::create($effectiveStart, $periodEnd) as $date) {
            $dateKey = $date->toDateString();

            if (in_array($date->dayOfWeek, $offDays, true)) {
                $weekendDays++;
                continue;
            }

            $workingDays++;
            /** @var AttendanceRecord|null $record */
            $record = $records->get($dateKey);

            if (!$record) {
                $absentDays++;
                continue;
            }

            $status = strtolower((string) ($record->status ?? 'absent'));

            if (in_array($status, ['present', 'late', 'half_day'], true)) {
                $presentDays++;
                $totalWorkingMinutes += (int) ($record->total_working_minutes ?? 0);
                $overtimeMinutes += max(0, (int) ($record->overtime_minutes ?? 0));

                $recordLateMinutes = (int) ($record->late_minutes ?? 0);
                $isLate = (bool) ($record->is_late ?? false);
                if (!$isLate && $recordLateMinutes <= 0 && !empty($record->check_in)) {
                    $recordLateMinutes = $this->calculateLateMinutes($employee, $dateKey, (string) $record->check_in);
                    $isLate = $recordLateMinutes > 0;
                }

                if ($isLate || $status === 'late') {
                    $totalLateCount++;
                    $totalLateMinutes += max(0, $recordLateMinutes);
                }

                continue;
            }

            if ($status === 'leave') {
                $leaveDays++;
                continue;
            }

            if ($status === 'holiday') {
                $holidayDays++;
                continue;
            }

            if ($status === 'weekend') {
                $weekendDays++;
                continue;
            }

            $absentDays++;
        }

        $lateDays = $totalLateCount;

        return [
            'working_days'           => $workingDays,
            'present_days'           => $presentDays,
            'absent_days'            => $absentDays,
            'leave_days'             => $leaveDays,
            'holiday_days'           => $holidayDays,
            'weekend_days'           => $weekendDays,
            'late_days'              => $lateDays,
            'total_late_count'       => $totalLateCount,
            'total_late_minutes'     => $totalLateMinutes,
            'total_working_minutes'  => $totalWorkingMinutes,
            'overtime_minutes'       => $overtimeMinutes,
            'no_attendance_warning'  => $records->isEmpty(),
        ];
    }

    private function calculateLateMinutes(Employee $employee, string $attendanceDate, string $checkInTime): int
    {
        $shiftStart = $this->resolveShiftStart($employee);
        $shiftStartAt = Carbon::parse($attendanceDate . ' ' . $shiftStart);
        $checkInAt = Carbon::parse($attendanceDate . ' ' . substr($checkInTime, 0, 8));

        return $checkInAt->gt($shiftStartAt)
            ? $shiftStartAt->diffInMinutes($checkInAt)
            : 0;
    }

    private function resolveShiftStart(Employee $employee): string
    {
        $defaultShiftStart = (string) config('payroll.shift_start', '09:00');
        $shiftStart = !empty($employee->shift_start_time)
            ? substr((string) $employee->shift_start_time, 0, 5)
            : $defaultShiftStart;

        static $hasShiftTable = null;
        $shiftName = trim((string) ($employee->shift ?? ''));

        if ($shiftName !== '') {
            if ($hasShiftTable === null) {
                $hasShiftTable = DB::getSchemaBuilder()->hasTable('attendance_shifts');
            }

            if ($hasShiftTable) {
                $match = DB::table('attendance_shifts')
                    ->when(!empty($employee->branch_id), function ($query) use ($employee) {
                        $query->where(function ($inner) use ($employee) {
                            $inner->where('branch_id', $employee->branch_id)
                                ->orWhereNull('branch_id');
                        });
                    })
                    ->whereRaw('LOWER(shift_name) = ?', [strtolower($shiftName)])
                    ->orderByDesc('is_default')
                    ->first();

                if ($match) {
                    $candidateStart = substr((string) $match->start_time, 0, 5);
                    if (preg_match('/^\d{2}:\d{2}$/', $candidateStart)) {
                        $shiftStart = $candidateStart;
                    }
                }
            }
        }

        return preg_match('/^\d{2}:\d{2}$/', $shiftStart)
            ? $shiftStart
            : $defaultShiftStart;
    }

    public function findDoctorByEmployee(Employee $employee): ?Doctor
    {
        $employeeName = Str::lower(trim($employee->name));

        if ($this->doctorNameMap === null) {
            $this->doctorNameMap = Doctor::query()
                ->get()
                ->keyBy(fn (Doctor $doctor) => Str::lower(trim($doctor->name)));
        }

        return $this->doctorNameMap->get($employeeName);
    }

    public function buildDoctorMetrics(?Doctor $doctor, Carbon $periodStart, Carbon $periodEnd): array
    {
        if (!$doctor) {
            return [
                'satisfactory_sessions_count' => 0,
                'treatment_extension_revenue' => 0.0,
                'assessment_revenue' => 0.0,
                'reference_count' => 0,
                'personal_patient_revenue' => 0.0,
                'high_satisfaction_count' => 0,
            ];
        }

        $sessionQuery = TreatmentSession::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('created_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()]);

        $satisfactorySessionsCount = (clone $sessionQuery)
            ->where('con_status', 1)
            ->count();

        $treatmentExtensionRevenue = (clone $sessionQuery)
            ->where('session_number', '>', 1)
            ->sum('session_fee');

        $assessmentRevenue = Checkup::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('created_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->sum(DB::raw('COALESCE(paid_amount, fee, 0)'));

        $referenceCount = Checkup::query()
            ->where('referred_by', $doctor->id)
            ->whereBetween('created_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->where(function ($q) use ($doctor) {
                $q->whereNull('doctor_id')->orWhere('doctor_id', '!=', $doctor->id);
            })
            ->count();

        $personalPatientRevenue = Checkup::query()
            ->where('referred_by', $doctor->id)
            ->where('doctor_id', $doctor->id)
            ->whereBetween('created_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->sum(DB::raw('COALESCE(paid_amount, fee, 0)'));

        $highSatisfactionCount = DB::table('feedback')
            ->where('doctorid', $doctor->id)
            ->where('satisfaction', '>=', (int) config('payroll.bonuses.satisfaction_threshold', 90))
            ->whereBetween('created_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->count();

        return [
            'satisfactory_sessions_count' => $satisfactorySessionsCount,
            'treatment_extension_revenue' => (float) $treatmentExtensionRevenue,
            'assessment_revenue' => (float) $assessmentRevenue,
            'reference_count' => $referenceCount,
            'personal_patient_revenue' => (float) $personalPatientRevenue,
            'high_satisfaction_count' => $highSatisfactionCount,
        ];
    }

    /**
     * Fetch all adjustments for a payroll:
     * - Adjustments directly linked to this payroll record
     * - Standalone (pre-payroll) adjustments for same employee/month/year
     */
    public function getAdjustmentsForPayroll(AttendancePayroll $payroll): Collection
    {
        return PayrollAdjustment::query()
            ->where(function ($q) use ($payroll) {
                $q->where('payroll_id', $payroll->id)
                  ->orWhere(function ($q2) use ($payroll) {
                      $q2->whereNull('payroll_id')
                         ->where('employee_id', $payroll->employee_id)
                         ->where('month', $payroll->month)
                         ->where('year', $payroll->year);
                  });
            })
            ->get();
    }

    /**
     * Fetch standalone (pre-payroll) adjustments for an employee in a given month/year.
     */
    public function getStandaloneAdjustmentsForEmployee(int $employeeId, int $month, int $year): Collection
    {
        return PayrollAdjustment::query()
            ->whereNull('payroll_id')
            ->where('employee_id', $employeeId)
            ->where('month', $month)
            ->where('year', $year)
            ->get();
    }

    /**
     * After a payroll is saved, link any standalone adjustments to it.
     */
    public function linkStandaloneAdjustmentsToPayroll(AttendancePayroll $payroll): void
    {
        PayrollAdjustment::query()
            ->whereNull('payroll_id')
            ->where('employee_id', $payroll->employee_id)
            ->where('month', $payroll->month)
            ->where('year', $payroll->year)
            ->update(['payroll_id' => $payroll->id]);
    }

    public function addAdjustment(array $data): PayrollAdjustment
    {
        return PayrollAdjustment::query()->create($data);
    }
}
