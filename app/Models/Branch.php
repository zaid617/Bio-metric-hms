<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
      protected $fillable = [
        'name',
        'address',
        'phone',
        'prefix',
        'status',
        'fee',
        'balance',
        'city',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }
    public function settings()
{
    return $this->hasOne(BranchSetting::class);
}
public function generalSetting()
{
    return $this->hasOne(GeneralSetting::class);
}

}
