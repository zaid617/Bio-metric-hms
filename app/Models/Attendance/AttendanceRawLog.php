<?php

namespace App\Models\Attendance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRawLog extends Model
{
    protected $fillable = [
        'device_id',
        'device_user_uid',
        'user_id_on_device',
        'punch_time',
        'punch_type',
        'verify_type',
        'work_code',
        'is_processed',
        'processed_at',
    ];

    protected $casts = [
        'device_user_uid' => 'integer',
        'punch_time' => 'datetime',
        'punch_type' => 'integer',
        'verify_type' => 'integer',
        'work_code' => 'integer',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the device
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    /**
     * Scope for unprocessed logs
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    /**
     * Scope for processed logs
     */
    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    /**
     * Mark as processed
     */
    public function markAsProcessed()
    {
        $this->update([
            'is_processed' => true,
            'processed_at' => now(),
        ]);
    }
}
