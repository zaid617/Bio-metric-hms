<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AssignRolesSeeder extends Seeder
{
    public function run(): void
    {
        $receptionist = User::where('email', 'receptionist@gmail.com')->first();
        if ($receptionist) {
            $receptionist->assignRole('receptionist');
        }

        $admin = User::where('email', 'admin@example.com')->first();
        if ($admin) {
            $admin->assignRole('admin');
        }

        $doctor = User::where('email', 'doctor@gmail.com')->first();
        if ($doctor) {
            $doctor->assignRole('doctor');
        }
    }
}
