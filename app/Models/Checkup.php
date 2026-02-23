<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkup extends Model
{
    use HasFactory;

    // ✅ Add new fields to fillable
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'referred_by',
        'branch_id',
        'fee',
        'paid_amount',
        'payment_method',
        'checkup_status',
    ];

    // Relations
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
