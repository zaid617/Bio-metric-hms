<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          DB::table('patients')->insert([
            [
                'name' => 'Ali Raza',
                'email' => 'ali@example.com',
                'phone' => '03123456789',
                'branch_id' => 1,
                'cnic' => '35202-1234567-1',
                'guardian_name' => 'Mohammad Raza',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
