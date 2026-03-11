<?php

namespace App\Modules\Payroll\Models;

use App\Models\Attendance\AttendancePayroll;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustment extends Model
{
    protected $table = 'payroll_adjustments';

    protected $fillable = [
        'payroll_id',   // nullable — null means standalone/pre-payroll adjustment
        'employee_id',
        'month',
        'year',
        'adjustment_type',  // earning | deduction | award
        'code',
        'title',            // human-readable label for admin UI
        'amount',
        'notes',
        'reason',           // alias for notes used by admin form
        'meta',
        'created_by',
    ];

    protected $casts = [
        'payroll_id' => 'integer',
        'month' => 'integer',
        'year' => 'integer',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(AttendancePayroll::class, 'payroll_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    /** Only standalone adjustments (not linked to any payroll yet) */
    public function scopeStandalone(Builder $query): Builder
    {
        return $query->whereNull('payroll_id');
    }

    /** Adjustments for a specific employee/month/year */
    public function scopeForPeriod(Builder $query, int $employeeId, int $month, int $year): Builder
    {
        return $query->where('employee_id', $employeeId)
                     ->where('month', $month)
                     ->where('year', $year);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function isStandalone(): bool
    {
        return $this->payroll_id === null;
    }

    /** Friendly label for type+code */
    public function getDisplayLabelAttribute(): string
    {
        return $this->title ?: str_replace('_', ' ', $this->code ?? $this->adjustment_type);
    }
}
