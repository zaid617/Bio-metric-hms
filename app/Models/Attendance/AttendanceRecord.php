<?php

namespace App\Models\Attendance;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'branch_id',
        'device_id',
        'attendance_date',
        'check_in',
        'check_out',
        'check_in_raw_log_id',
        'check_out_raw_log_id',
        'total_working_minutes',
        'overtime_minutes',
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
     * Get check-in raw log
     */
    public function checkInRawLog(): BelongsTo
    {
        return $this->belongsTo(AttendanceRawLog::class, 'check_in_raw_log_id');
    }

    /**
     * Get check-out raw log
     */
    public function checkOutRawLog(): BelongsTo
    {
        return $this->belongsTo(AttendanceRawLog::class, 'check_out_raw_log_id');
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
