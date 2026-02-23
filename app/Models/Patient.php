<?php

namespace App\Models;
use App\Models\Patient;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;
     protected $fillable = [
    'prefix',
    'mr',
    'name',
    'gender',
    'guardian_name',
    'age',
    'phone',
    'cnic',
    'address',
    'branch_id',
    'type_select',
    'sub_select',

];

    protected static function booted()
    {
        static::created(function ($patient) {
            if (!$patient->mr) {
                $branch = $patient->branch()->first();
                $prefix = $branch ? $branch->prefix : 'MR'; // اگر branch میں prefix نہ ہو تو default MR

                // patient ID سے padded MR code
                $patient->mr = $prefix . '-' . str_pad($patient->id, 5, '0', STR_PAD_LEFT);

                $patient->save();
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function checkups()
    {
        return $this->hasMany(Checkup::class);
    }

    public function payments()
{
    return $this->hasMany(Payment::class);
}
public function invoices()
{
    return $this->hasMany(Invoice::class);
}
}
