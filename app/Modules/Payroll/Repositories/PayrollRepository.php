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

    public function buildAttendanceMetrics(int $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $records = AttendanceRecord::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get();

        $presentDays = $records->whereIn('status', ['present', 'late', 'half_day'])->count();
        $absentDays = $records->where('status', 'absent')->count();
        $lateDays = $records->where('status', 'late')->count();
        $workingMinutes = (int) $records->sum('total_working_minutes');
        $overtimeMinutes = (int) $records->sum('overtime_minutes');

        return [
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'total_working_minutes' => $workingMinutes,
            'overtime_minutes' => $overtimeMinutes,
        ];
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

    public function getAdjustmentsForPayroll(AttendancePayroll $payroll): Collection
    {
        return PayrollAdjustment::query()
            ->where('payroll_id', $payroll->id)
            ->get();
    }

    public function addAdjustment(array $data): PayrollAdjustment
    {
        return PayrollAdjustment::query()->create($data);
    }
}
