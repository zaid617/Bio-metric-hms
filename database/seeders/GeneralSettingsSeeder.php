<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeneralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            // Sab branches ko laiye
        $branches = DB::table('branches')->get();

        // Har branch ke liye ek general_setting row insert/update karein
        foreach ($branches as $branch) {
            DB::table('general_settings')->updateOrInsert(
                ['branch_id' => $branch->id], // condition
                ['default_checkup_fee' => 1000] // jo fee deni hai
            );
        }
    }
    }

