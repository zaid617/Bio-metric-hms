<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateMonthlySalaries extends Command
{
  protected $signature = 'generate:monthly-salaries';


    protected $description = 'Automatically generate monthly salary records for all employees';

    public function handle()
    {
        // Get the first day of the current month (e.g., 2025-07-01)
        $month = Carbon::now()->startOfMonth()->format('Y-m-d');

        // Get all employees from the database
        $employees = DB::table('employees')->get();

        foreach ($employees as $employee) {
            // Check if a salary record for this employee already exists for this month
            $exists = DB::table('employee_salaries')
                ->where('employee_id', $employee->id)
                ->where('month', $month)
                ->exists();

            if (!$exists) {
                // Default values
                $basic = $employee->basic_salary ?? 0;
                $allowances = $employee->allowances ?? 0;
                $bonuses = 0;
                $deductions = 0;

                // Calculate net salary
                $net = $basic + $allowances + $bonuses - $deductions;

                // Insert salary record
                DB::table('employee_salaries')->insert([
                    'employee_id'    => $employee->id,
                    'month'          => $month,
                    'basic_salary'   => $basic,
                    'allowances'     => $allowances,
                    'bonuses'        => $bonuses,
                    'deductions'     => $deductions,
                    'net_salary'     => $net,
                    'payment_status' => 'Pending',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        $this->info("✅ Salary records successfully generated for month: $month");
    }
}
