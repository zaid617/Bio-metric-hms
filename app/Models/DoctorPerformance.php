<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorPerformance extends Model
{
     protected $fillable = ['doctor_id', 'patients_seen', 'rating', 'remarks', 'report_date'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
