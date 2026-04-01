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
        'name', 'designation', 'branch_id', 'basic_salary', 'working_hours', 'shift', 'shift_start_time', 'phone', 'joining_date',
        'device_id', 'user_id_on_device',
    ];

      protected $casts = [
        'working_hours' => 'float',
      ];

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
