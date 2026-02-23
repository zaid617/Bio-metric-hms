<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::firstOrCreate(
            ['prefix' => 'WP'], 
            ['name' => 'Main Branch Rawalpindi']
        );
    }
}
