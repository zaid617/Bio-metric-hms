<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TreatmentSession;

class SessionInstallment extends Model
{
    protected $table = 'session_installments';

    protected $fillable = [
        'session_id',
        'amount',
        'payment_date',
        'payment_method',
    ];

    protected $dates = ['payment_date'];

    public function session()
    {
        return $this->belongsTo(TreatmentSession::class, 'session_id');
    }
}
