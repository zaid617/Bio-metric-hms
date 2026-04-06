<?php

namespace App\Console\Commands;

use App\Modules\Payroll\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlySalaries extends Command
{
        protected $signature = 'generate:monthly-salaries {--month=} {--year=} {--force}';


    protected $description = 'Automatically generate monthly salary records for all employees';

    public function __construct(private readonly PayrollService $payrollService)
    {
        parent::__construct();
    }

    public function handle()
    {
        $month = (int) ($this->option('month') ?: Carbon::now()->month);
        $year = (int) ($this->option('year') ?: Carbon::now()->year);

        $result = $this->payrollService->generateMonthlyPayroll(
            $month,
            $year,
            null,
            null,
            (bool) $this->option('force')
        );

        $updatedCount = collect($result['updated'] ?? [])->count();
        $this->info("Payroll generation complete for {$month}/{$year}. Created: {$result['created']->count()}, Updated: {$updatedCount}, Skipped: {$result['skipped']->count()}.");

        return self::SUCCESS;
    }
}
