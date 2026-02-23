<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentOutstanding extends Model
{
    
    protected $fillable = [
        'session_id',
        'checkup_id',
        'payment_details',
    ];

    // 🔁 Optional relationships (if needed later)
    public function treatmentSession()
    {
        return $this->belongsTo(\App\Models\TreatmentSession::class, 'session_id');
    }
}
