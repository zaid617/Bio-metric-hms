<?php

namespace App\Models\Attendance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'sync_type',
        'status',
        'records_fetched',
        'records_new',
        'records_duplicate',
        'error_message',
        'started_at',
        'completed_at',
        'created_at',
    ];

    protected $casts = [
        'records_fetched' => 'integer',
        'records_new' => 'integer',
        'records_duplicate' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the device
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    /**
     * Scope for successful syncs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed syncs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Calculate duration in seconds
     */
    public function getDurationAttribute()
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at);
        }
        return null;
    }
}
