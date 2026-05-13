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
    'department_id',
        'department',
        'basic_salary',
  'incentive_sunday_roster',
  'incentive_home_visit',
  'incentive_speech_therapy',
  'incentive_dry_needling',
        'allowance_allied_health_council',
        'allowance_house_job',
        'allowance_conveyance',
        'allowance_medical',
        'allowance_house_rent',
        'allowance_branch_manager',
        'allowance_assistant_branch_manager',
        'other_allowance',
        'other_allowance_label',
        'other_allowances',
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
        'incentive_sunday_roster' => 'decimal:2',
        'incentive_home_visit' => 'decimal:2',
        'incentive_speech_therapy' => 'decimal:2',
        'incentive_dry_needling' => 'decimal:2',
        'allowance_allied_health_council' => 'decimal:2',
        'allowance_house_job' => 'decimal:2',
        'allowance_conveyance' => 'decimal:2',
        'allowance_medical' => 'decimal:2',
        'allowance_house_rent' => 'decimal:2',
        'allowance_branch_manager' => 'decimal:2',
        'allowance_assistant_branch_manager' => 'decimal:2',
        'other_allowance' => 'decimal:2',
        'other_allowances' => 'array',
        'working_hours' => 'float',
      ];

      public function getTotalIncentivesAttribute(): float
      {
        return (float) $this->incentive_sunday_roster
            + (float) $this->incentive_home_visit
            + (float) $this->incentive_speech_therapy
            + (float) $this->incentive_dry_needling;
      }

      public function getTotalAllowancesAttribute(): float
      {
        return (float) $this->allowance_allied_health_council
            + (float) $this->allowance_house_job
            + (float) $this->allowance_conveyance
            + (float) $this->allowance_medical
          + (float) $this->allowance_house_rent
          + (float) $this->allowance_branch_manager
          + (float) $this->allowance_assistant_branch_manager;
      }

      public function getGrossSalaryAttribute(): float
      {
        return (float) $this->basic_salary
        + (float) $this->total_incentives
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
            'allowance_branch_manager',
            'allowance_assistant_branch_manager',
            'other_allowance',
            'other_allowance_label',
            'other_allowances',
        ]);
      }

      public function scopeWithIncentives($query)
      {
        return $query->addSelect([
            'incentive_sunday_roster',
            'incentive_home_visit',
            'incentive_speech_therapy',
            'incentive_dry_needling',
        ]);
      }

      public function branch(): BelongsTo
      {
        return $this->belongsTo(Branch::class);
      }

      public function departmentRecord(): BelongsTo
      {
        return $this->belongsTo(Department::class, 'department_id');
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
