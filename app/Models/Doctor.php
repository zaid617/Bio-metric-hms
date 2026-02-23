<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Doctor extends Authenticatable
{
    use HasRoles, Notifiable {
        HasRoles::hasPermissionTo as traitHasPermissionTo; // alias for override
    }

    // ──────────────── Guard for Spatie ────────────────
    protected $guard_name = 'doctor';
    protected $table = 'doctors';

    // ──────────────── Fillable Columns ────────────────
    protected $fillable = [
        'prefix',
        'branch_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'specialization',
        'password',
        'cnic',
        'dob',
        'last_education',
        'document',
        'picture',
        'status',
        'shift',
    ];

    // ──────────────── Hidden Columns ────────────────
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ──────────────── Relationships ────────────────
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function availabilities()
    {
        return $this->hasMany(DoctorAvailability::class);
    }

    public function performances()
    {
        return $this->hasMany(DoctorPerformance::class);
    }

    public function checkups()
    {
        return $this->hasMany(Checkup::class, 'doctor_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'doctor_id');
    }

    public function treatmentSessions()
    {
        return $this->hasMany(TreatmentSession::class, 'doctor_id');
    }

    public function completedSessions()
    {
        return $this->hasMany(SessionTime::class, 'completed_by_doctor_id');
    }

    // ──────────────── Accessor for full name ────────────────
    public function getNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    // ──────────────── Polymorphic Denied Permissions ────────────────
    public function deniedPermissions(): MorphToMany
    {
        return $this->morphToMany(
            Permission::class,
            'model',               // morph name from morphs('model') in migration
            'denied_permissions',  // table name
            'model_id',            // current model id column
            'permission_id'        // related model id column
        );
    }

    // ──────────────── Override hasPermissionTo for denied check ────────────────
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $guardName = $guardName ?? $this->guard_name;

        // Agar permission string hai, fetch Permission model
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)
                ->where('guard_name', $guardName)
                ->first();

            if (!$permission) return false;
        }

        // Denied permissions check
        if ($this->deniedPermissions->contains('id', $permission->id)) {
            return false;
        }

        // Spatie trait method use karo
        return $this->traitHasPermissionTo($permission, $guardName);
    }

    // ──────────────── Booted method for auto role assignment ────────────────
    protected static function booted()
    {
        static::created(function ($doctor) {
            if (!$doctor->hasRole('doctor')) {
                $doctor->assignRole('doctor'); // Automatically assign doctor role
            }
        });
    }
}
