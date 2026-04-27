<?php

namespace App\Models\Attendance;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'branch_id',
        'device_id',
        'attendance_date',
        'check_in',
        'check_out',
        'total_working_minutes',
        'overtime_minutes',
        'is_late',
        'late_minutes',
        'status',
        'is_checkout_missing',
        'auto_checkout_applied',
        'auto_checkout_time',
        'admin_note',
        'is_manually_adjusted',
        'adjusted_by',
        'adjusted_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'total_working_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'is_checkout_missing' => 'boolean',
        'auto_checkout_applied' => 'boolean',
        'is_manually_adjusted' => 'boolean',
        'adjusted_at' => 'datetime',
    ];

    /**
     * Get the employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the device
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    /**
     * Get the user who adjusted
     */
    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Scope for specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    /**
     * Scope for specific status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for present records
     */
    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'late', 'half_day']);
    }

    /**
     * Calculate working hours
     */
    public function getWorkingHoursAttribute()
    {
        return $this->total_working_minutes ? round($this->total_working_minutes / 60, 2) : 0;
    }

    /**
     * Calculate overtime hours
     */
    public function getOvertimeHoursAttribute()
    {
        return $this->overtime_minutes ? round($this->overtime_minutes / 60, 2) : 0;
    }

    /**
     * Calculate overtime minutes based on employee's standard working hours
     */
    public function getCalculatedOvertimeMinutesAttribute()
    {
        if (!$this->total_working_minutes) {
            return 0;
        }

        $standardMinutes = ($this->employee && $this->employee->working_hours)
            ? (float) $this->employee->working_hours * 60
            : (float) config('payroll.default_shift_hours', 8) * 60;

        $overtime = $this->total_working_minutes - $standardMinutes;

        return $overtime > 0 ? (int) $overtime : 0;
    }

    /**
     * Compute the current attendance status using the active payroll grace period.
     */
    public function getEffectiveStatusAttribute(): string
    {
        $storedStatus = strtolower((string) ($this->status ?? 'absent'));

        if (in_array($storedStatus, ['leave', 'holiday', 'weekend', 'absent'], true) && empty($this->check_in)) {
            return $storedStatus;
        }

        if (empty($this->check_in) || !$this->employee) {
            return $storedStatus;
        }

        $shiftRule = $this->resolveShiftRuleForDisplay();
        $attendanceDate = $this->attendance_date instanceof Carbon
            ? $this->attendance_date->toDateString()
            : (string) $this->attendance_date;

        $checkIn = Carbon::parse($attendanceDate . ' ' . substr((string) $this->check_in, 0, 8));
        $deadline = (clone $shiftRule['shift_start_at'])->addMinutes($shiftRule['grace_minutes']);

        if ($checkIn->gt($deadline)) {
            return 'late';
        }

        return in_array($storedStatus, ['present', 'late', 'half_day'], true) ? 'present' : $storedStatus;
    }

    /**
     * Compute current late minutes using the active payroll grace period.
     */
    public function getEffectiveLateMinutesAttribute(): int
    {
        if (empty($this->check_in) || !$this->employee) {
            return (int) ($this->late_minutes ?? 0);
        }

        $shiftRule = $this->resolveShiftRuleForDisplay();
        $attendanceDate = $this->attendance_date instanceof Carbon
            ? $this->attendance_date->toDateString()
            : (string) $this->attendance_date;

        $checkIn = Carbon::parse($attendanceDate . ' ' . substr((string) $this->check_in, 0, 8));
        $deadline = (clone $shiftRule['shift_start_at'])->addMinutes($shiftRule['grace_minutes']);

        return $checkIn->gt($deadline)
            ? $deadline->diffInMinutes($checkIn)
            : 0;
    }

    /**
     * Resolve the active shift rule for display-time calculations.
     */
    protected function resolveShiftRuleForDisplay(): array
    {
        $employee = $this->employee;
        $defaultShiftStart = (string) config('payroll.shift_start', '09:00');
        $defaultGrace = (int) config('payroll.late_grace_minutes', 15);
        $shiftStart = !empty($employee?->shift_start_time)
            ? substr((string) $employee->shift_start_time, 0, 5)
            : $defaultShiftStart;
        $graceMinutes = $defaultGrace;

        static $hasShiftTable = null;
        $shiftName = trim((string) ($employee?->shift ?? ''));

        if ($shiftName !== '') {
            if ($hasShiftTable === null) {
                $hasShiftTable = DB::getSchemaBuilder()->hasTable('attendance_shifts');
            }

            if ($hasShiftTable) {
                $match = DB::table('attendance_shifts')
                    ->when(!empty($employee?->branch_id), function ($query) use ($employee) {
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

                    $graceMinutes = (int) ($match->grace_period_minutes ?? $graceMinutes);
                }
            }
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $shiftStart)) {
            $shiftStart = $defaultShiftStart;
        }

        $dateString = $this->attendance_date instanceof Carbon
            ? $this->attendance_date->toDateString()
            : (string) $this->attendance_date;

        return [
            'shift_start_at' => Carbon::parse($dateString . ' ' . $shiftStart),
            'grace_minutes' => max(0, $graceMinutes),
        ];
    }

    /**
     * Apply auto checkout
     */
    public function applyAutoCheckout($autoCheckoutTime)
    {
        $this->update([
            'check_out' => $autoCheckoutTime,
            'auto_checkout_applied' => true,
            'auto_checkout_time' => $autoCheckoutTime,
            'is_checkout_missing' => true,
        ]);
    }
}
