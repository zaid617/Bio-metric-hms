<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserRoleSeeder extends Seeder
{
    public function run()
    {
        // Admins
        $admins = ['admin@gmail.com', 'admin@example.com'];
        foreach ($admins as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->assignRole('admin');
            }
        }

        // Managers
        $managers = ['manager@gmail.com', 'manager@example.com'];
        foreach ($managers as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->assignRole('manager');
            }
        }

        // Receptionists
        $receptionists = ['receptionist@gmail.com'];
        foreach ($receptionists as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->assignRole('receptionist');
            }
        }

        // Doctors
        $doctors = ['doctor@gmail.com'];
        foreach ($doctors as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->assignRole('doctor');
            }
        }

        // Other users (optional, default 'user' role)
        $others = ['nomi@gmail.com'];
        foreach ($others as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->assignRole('user');
            }
        }

        $this->command->info('Roles assigned successfully!');
    }
}
