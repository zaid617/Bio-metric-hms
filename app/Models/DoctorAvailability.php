<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorAvailability extends Model
{
    protected $fillable = [
        'doctor_id', 'date', 'day_of_week',
        'morning_start', 'morning_end', 'morning_leave',
        'evening_start', 'evening_end', 'evening_leave'
    ];

    protected $casts = [
        'date' => 'date',
        'morning_leave' => 'boolean',
        'evening_leave' => 'boolean',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
