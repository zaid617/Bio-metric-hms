<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class BranchesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          DB::table('branches')->insert([
            'name' => 'Lahore Branch',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
    }

