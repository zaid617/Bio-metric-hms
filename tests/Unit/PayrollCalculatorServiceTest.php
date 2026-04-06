<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Modules\Payroll\Services\PayrollCalculatorService;
use App\Modules\Payroll\Types\PayrollAwardType;
use App\Modules\Payroll\Types\PayrollDeductionType;
use App\Modules\Payroll\Types\PayrollEarningType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PayrollCalculatorServiceTest extends TestCase
{
    public function test_net_pay_includes_allowances_incentives_awards_and_deductions(): void
    {
        $service = new PayrollCalculatorService();

        $employee = new Employee([
            'basic_salary' => 10000,
            'allowance_allied_health_council' => 500,
            'allowance_house_job' => 400,
            'allowance_conveyance' => 300,
            'allowance_medical' => 200,
            'allowance_house_rent' => 100,
            'other_allowance' => 250,
            'incentive_sunday_roster' => 300,
            'incentive_home_visit' => 200,
            'incentive_speech_therapy' => 100,
            'incentive_dry_needling' => 50,
        ]);

        $attendanceMetrics = [
            'working_days' => 20,
            'present_days' => 18,
            'absent_days' => 2,
            'leave_days' => 0,
            'holiday_days' => 0,
            'weekend_days' => 8,
            'late_days' => 4,
            'total_late_count' => 4,
            'total_late_minutes' => 30,
            'total_working_minutes' => 8640,
            'overtime_minutes' => 0,
        ];

        $adjustments = new Collection([
            [
                'adjustment_type' => 'earning',
                'code' => PayrollEarningType::INCENTIVE_HOME_VISIT,
                'amount' => 1000,
                'notes' => 'Home visit boost',
            ],
            [
                'adjustment_type' => 'earning',
                'code' => PayrollEarningType::CUSTOM,
                'amount' => 250,
                'notes' => 'Special incentive',
            ],
            [
                'adjustment_type' => 'award',
                'code' => PayrollAwardType::CUSTOM,
                'amount' => 600,
                'notes' => 'Top performer',
            ],
            [
                'adjustment_type' => 'deduction',
                'code' => PayrollDeductionType::TAX,
                'amount' => 300,
                'notes' => 'Income tax',
            ],
            [
                'adjustment_type' => 'deduction',
                'code' => PayrollDeductionType::CUSTOM,
                'amount' => 150,
                'notes' => 'Late reporting',
            ],
        ]);

        $result = $service->calculate(
            $employee,
            Carbon::create(2026, 4, 1),
            Carbon::create(2026, 4, 30),
            $attendanceMetrics,
            [],
            $adjustments
        );

        $this->assertEqualsWithDelta(12550.00, (float) $result['final_salary'], 0.001);
        $this->assertEqualsWithDelta(300.00, (float) $result['tax'], 0.001);
        $this->assertEqualsWithDelta(150.00, (float) $result['other_deduction'], 0.001);

        $earningRows = collect($result['earnings']);
        $this->assertNotNull(
            $earningRows->first(fn ($line) => ($line['type'] ?? '') === PayrollEarningType::INCENTIVE_HOME_VISIT
                && abs((float) ($line['amount'] ?? 0) - 1000) < 0.001
                && ($line['notes'] ?? '') === 'Home visit boost')
        );

        $awardRows = collect($result['awards']);
        $this->assertNotNull(
            $awardRows->first(fn ($line) => ($line['type'] ?? '') === PayrollAwardType::CUSTOM
                && abs((float) ($line['amount'] ?? 0) - 600) < 0.001
                && ($line['notes'] ?? '') === 'Top performer')
        );

        $deductionRows = collect($result['deductions']);
        $this->assertNotNull(
            $deductionRows->first(fn ($line) => ($line['type'] ?? '') === PayrollDeductionType::CUSTOM
                && abs((float) ($line['amount'] ?? 0) - 150) < 0.001
                && ($line['notes'] ?? '') === 'Late reporting')
        );
    }
}
