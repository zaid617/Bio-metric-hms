<?php

namespace App\Models;

use App\Models\Attendance\AttendanceDevice;
use App\Models\Attendance\AttendancePayroll;
use App\Models\Attendance\AttendanceRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
      protected $fillable = [
        'prefix',
        'name',
        'designation',
        'branch_id',
        'department',
        'basic_salary',
        'allowance_allied_health_council',
        'allowance_house_job',
        'allowance_conveyance',
        'allowance_medical',
        'allowance_house_rent',
        'other_allowance',
        'other_allowance_label',
        'working_hours',
        'shift',
        'shift_start_time',
        'phone',
        'joining_date',
        'device_id',
        'user_id_on_device',
    ];

      protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowance_allied_health_council' => 'decimal:2',
        'allowance_house_job' => 'decimal:2',
        'allowance_conveyance' => 'decimal:2',
        'allowance_medical' => 'decimal:2',
        'allowance_house_rent' => 'decimal:2',
        'other_allowance' => 'decimal:2',
        'working_hours' => 'float',
      ];

      public function getTotalIncentivesAttribute(): float
      {
        return 0.0;
      }

      public function getTotalAllowancesAttribute(): float
      {
        return (float) $this->allowance_allied_health_council
            + (float) $this->allowance_house_job
            + (float) $this->allowance_conveyance
            + (float) $this->allowance_medical
            + (float) $this->allowance_house_rent;
      }

      public function getGrossSalaryAttribute(): float
      {
        return (float) $this->basic_salary
            + (float) $this->total_allowances
            + (float) $this->other_allowance;
      }

      public function scopeWithAllowances($query)
      {
        return $query->addSelect([
            'allowance_allied_health_council',
            'allowance_house_job',
            'allowance_conveyance',
            'allowance_medical',
            'allowance_house_rent',
            'other_allowance',
            'other_allowance_label',
        ]);
      }

      public function scopeWithIncentives($query)
      {
        return $query;
      }

      public function branch(): BelongsTo
      {
        return $this->belongsTo(Branch::class);
      }

      public function device(): BelongsTo
      {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
      }

      public function attendanceRecords(): HasMany
      {
        return $this->hasMany(AttendanceRecord::class);
      }

      public function payrolls(): HasMany
      {
        return $this->hasMany(AttendancePayroll::class);
      }
}
