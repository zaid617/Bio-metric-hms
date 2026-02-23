<?php

namespace Database\Seeders;
use App\Models\TreatmentSession;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TreatmentSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {  
      TreatmentSession::create([
    'checkup_id' => 1,
    'doctor_id' => 1,
    'session_date' => now(),
    'session_time' => '11:00:00', // ✅ Fix: 24-hour format
    'session_fee' => 2500,
    'status' => 'scheduled',
    'payment_status' => 'unpaid',
    'created_at' => now(),
    'updated_at' => now(),
]);

    }
}
