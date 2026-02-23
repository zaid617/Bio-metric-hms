<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
     protected $fillable = ['branch_id', 'default_checkup_fee'];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }
}
