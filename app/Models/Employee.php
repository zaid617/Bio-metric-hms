<?php

namespace App\Models;

use App\Models\Attendance\AttendanceDevice;
use App\Models\Attendance\AttendancePayroll;
use App\Models\Attendance\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    /**
     * All numeric salary component fields — used for snapshot gross calculation
     * and payroll historical lookup.
     */
    const SALARY_NUMERIC_FIELDS = [
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
    ];

    /**
     * All salary-related fields stored in each increment snapshot,
     * including non-numeric label and JSON array fields.
     */
    const SALARY_ALL_FIELDS = [
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
    ];
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
        'appointment_letter',
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

      public function salaryIncrements(): HasMany
      {
        return $this->hasMany(SalaryIncrement::class)->orderBy('effective_from', 'desc');
      }

      /**
       * Build a full snapshot of the current salary components on this model.
       */
      public function salarySnapshot(): array
      {
          $snapshot = [];
          foreach (self::SALARY_ALL_FIELDS as $field) {
              $snapshot[$field] = $this->$field;
          }
          return $snapshot;
      }

      /**
       * Apply a stored snapshot to this model's in-memory attributes.
       * Used by PayrollService before calling the calculator so that
       * historical salary values are used instead of current ones.
       * No DB write — caller must save if persistence is required.
       */
      public function applySnapshot(array $snapshot): void
      {
          foreach (self::SALARY_ALL_FIELDS as $field) {
              if (array_key_exists($field, $snapshot)) {
                  $this->$field = $snapshot[$field];
              }
          }
      }

      /**
       * Return the new_snapshot from the latest active increment that was
       * effective on or before $date. Returns null when no increment applies
       * (caller should fall back to current model values).
       */
      public function salaryAsOf(Carbon $date): ?array
      {
          $increment = $this->salaryIncrements()
              ->where('effective_from', '<=', $date->toDateString())
              ->first();

          return $increment?->new_snapshot;
      }

      /**
       * Gross salary from the latest active increment, or fall back to
       * the current gross_salary accessor if no increments exist.
       */
      public function currentSalary(): float
      {
          $latest = $this->salaryIncrements()->first();

          return $latest
              ? SalaryIncrement::grossFromSnapshot($latest->new_snapshot)
              : (float) $this->gross_salary;
      }
}
