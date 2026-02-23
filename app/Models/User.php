<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class User extends Authenticatable
{
    use HasFactory, HasRoles {
        HasRoles::hasPermissionTo as traitHasPermissionTo; // alias for overriding
    }

    /**
     * Fillable fields
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id', // branch relation ke liye
    ];

    /**
     * Branch relation
     * Each user belongs to one branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id'); 
        // users.branch_id -> branches.id
    }

    /**
     * 🔹 Denied permissions relation (polymorphic)
     */
    public function deniedPermissions(): MorphToMany
    {
        return $this->morphToMany(
            Permission::class,
            'model',               // morph name from morphs('model') in migration
            'denied_permissions',  // table
            'model_id',            // current model id column
            'permission_id'        // related model id column
        );
    }

    /**
     * 🔹 Override Spatie hasPermissionTo to check denied permissions
     * Supports dynamic guard based on user's guard_name
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // Dynamic guard
        $guardName = $this->guard_name ?? 'web';

        // Agar permission string hai, to fetch Permission model
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)
                ->where('guard_name', $guardName)
                ->first();

            if (!$permission) {
                return false; // permission exist nahi karti
            }
        }

        // Agar permission explicitly denied hai
        if ($this->deniedPermissions->contains('id', $permission->id)) {
            return false;
        }

        // Otherwise, use Spatie's trait method
        return $this->traitHasPermissionTo($permission, $guardName);
    }
}
