<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
     protected $fillable = [
        'checkup_id',
        'date',
        'time',
        'doctor_id',
        'status',
    ];
}
