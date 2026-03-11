<?php

namespace App\Modules\Payroll\Models;

use App\Models\Attendance\AttendancePayroll;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustment extends Model
{
    protected $table = 'payroll_adjustments';

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'month',
        'year',
        'adjustment_type',
        'code',
        'amount',
        'notes',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

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
}
