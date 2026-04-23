<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkup extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'referred_by',
        'referred_by_type',
        'referred_by_id',
        'referred_by_name',
        'branch_id',
        'fee',
        'paid_amount',
        'payment_method',
        'checkup_status',
        'description',
        'discount',
        'consultation_type',
        'pending_amount',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'pending_amount' => 'decimal:2',
    ];

    public static function calculatePendingAmount($fee, $discount, $paidAmount): float
    {
        $total = (float) ($fee ?? 0);
        $discountPercent = (float) ($discount ?? 0);
        $paidValue = (float) ($paidAmount ?? 0);
        $discountAmount = $total * ($discountPercent / 100);

        return max(0, round($total - $discountAmount - $paidValue, 2));
    }

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
