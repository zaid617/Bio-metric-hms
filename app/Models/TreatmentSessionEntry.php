<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentSessionEntry extends Model
{
    protected $fillable = [
        'treatment_session_id',
        'session_date',
        'session_time',
    ];

     public function treatmentSession()
{
    return $this->belongsTo(TreatmentSession::class, 'treatment_session_id');
}

}
