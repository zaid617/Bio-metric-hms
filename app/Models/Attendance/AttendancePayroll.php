<?php

namespace App\Models\Attendance;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\User;
use App\Modules\Payroll\Models\PayrollAdjustment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePayroll extends Model
{
    protected $fillable = [
        'employee_id',
        'branch_id',
        'month',
        'year',
        'payroll_period_start',
        'payroll_period_end',
        'total_working_days',
        'present_days',
        'absent_days',
        'leave_days',
        'holiday_days',
        'weekend_days',
        'late_days',
        'total_late_count',
        'total_late_minutes',
        'total_working_hours',
        'overtime_hours',
        'total_overtime_hours',
        'base_salary',
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
        'absent_deduction',
        'late_deduction',
        'tax',
        'provident_fund',
        'eobi',
        'advance',
        'loan',
        'other_deduction',
        'final_salary',
        'hourly_rate',
        'overtime_rate_multiplier',
        'calculated_salary',
        'overtime_pay',
        'deductions',
        'bonus',
        'final_settlement',
        'admin_adjustment_amount',
        'admin_adjustment_note',
        'status',
        'approved_by',
        'approved_at',
        'paid_at',
        'payment_method',
        'payment_reference',
        'earnings_breakdown',
        'deductions_breakdown',
        'awards_breakdown',
        'payslip_data',
        'generated_by',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'payroll_period_start' => 'date',
        'payroll_period_end' => 'date',
        'total_working_days' => 'integer',
        'present_days' => 'integer',
        'absent_days' => 'integer',
        'leave_days' => 'integer',
        'holiday_days' => 'integer',
        'weekend_days' => 'integer',
        'late_days' => 'integer',
        'total_late_count' => 'integer',
        'total_late_minutes' => 'integer',
        'total_working_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'total_overtime_hours' => 'decimal:2',
        'base_salary' => 'decimal:2',
        'basic_salary' => 'decimal:2',
        'additional_salary' => 'decimal:2',
        'overtime' => 'decimal:2',
        'satisfactory_sessions' => 'decimal:2',
        'treatment_extension_commission' => 'decimal:2',
        'satisfaction_bonus' => 'decimal:2',
        'assessment_bonus' => 'decimal:2',
        'reference_bonus' => 'decimal:2',
        'personal_patient_commission' => 'decimal:2',
        'awards_total' => 'decimal:2',
        'deductions_total' => 'decimal:2',
        'absent_deduction' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'tax' => 'decimal:2',
        'provident_fund' => 'decimal:2',
        'eobi' => 'decimal:2',
        'advance' => 'decimal:2',
        'loan' => 'decimal:2',
        'other_deduction' => 'decimal:2',
        'final_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'overtime_rate_multiplier' => 'decimal:2',
        'calculated_salary' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'deductions' => 'decimal:2',
        'bonus' => 'decimal:2',
        'final_settlement' => 'decimal:2',
        'admin_adjustment_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'earnings_breakdown' => 'array',
        'deductions_breakdown' => 'array',
        'awards_breakdown' => 'array',
        'payslip_data' => 'array',
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
     * Get approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class, 'payroll_id');
    }

    /**
     * Scope for specific status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for draft payrolls
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for approved payrolls
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for paid payrolls
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Recalculate final settlement
     */
    public function recalculateFinalSettlement()
    {
        $base = $this->final_salary ?: (
            $this->calculated_salary
            + $this->overtime_pay
            + $this->bonus
            - $this->deductions
        );

        $this->final_settlement = $base + $this->admin_adjustment_amount;
        $this->save();
    }
}
