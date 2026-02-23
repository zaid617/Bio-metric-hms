<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Creating a user with branch_id = 1
        User::create([
            'name' => 'Nomi',
            'email' => 'nomi@gmail.com',
            'password' => bcrypt('password'),
            'branch_id' => 1,
        ]);
    }
}
