<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(BranchSeeder::class);
        $this->call(PatientSeeder::class);
        $this->call(GeneralSettingsSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(AttendancePermissionsSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(UserRoleSeeder::class);


       // User::factory()->create([
           // 'name' => 'Test User',
          //  'email' => 'test@example.com',
        //]);
    }
}
