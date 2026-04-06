<?php

namespace App\Models\Attendance;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceDevice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'device_name',
        'device_serial_number',
        'ip_address',
        'port',
        'password',
        'com_key',
        'is_active',
        'last_synced_at',
        'connection_status',
        'sync_interval_minutes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'port' => 'integer',
        'sync_interval_minutes' => 'integer',
    ];

    /**
     * Get the branch that owns the device
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get sync logs
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(AttendanceSyncLog::class, 'device_id');
    }

    /**
     * Get attendance records
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'device_id');
    }

    /**
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for online devices
     */
    public function scopeOnline($query)
    {
        return $query->where('connection_status', 'online');
    }
}
