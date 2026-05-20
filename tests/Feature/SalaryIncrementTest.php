<?php

namespace Tests\Feature;

use App\Http\Requests\Employee\StoreSalaryIncrementRequest;
use App\Models\Employee;
use App\Models\SalaryIncrement;
use App\Models\User;
use App\Services\Employee\SalaryIncrementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SalaryIncrementTest extends TestCase
{
    use DatabaseTransactions;

    // ── helpers ──────────────────────────────────────────────────────────────

    private function createBranch(): int
    {
        return DB::table('branches')->insertGetId([
            'name'       => 'Test Branch',
            'prefix'     => 'TB',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDepartment(): int
    {
        return DB::table('departments')->insertGetId([
            'name'       => 'Test Department',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEmployee(array $overrides = []): Employee
    {
        $branchId     = $this->createBranch();
        $departmentId = $this->createDepartment();

        $id = DB::table('employees')->insertGetId(array_merge([
            'prefix'                           => 'Mr.',
            'name'                             => 'Test Employee',
            'designation'                      => 'Physiotherapist',
            'branch_id'                        => $branchId,
            'department_id'                    => $departmentId,
            'department'                       => 'Test Department',
            'shift'                            => 'Morning',
            'shift_start_time'                 => '09:00',
            'working_hours'                    => 8,
            'basic_salary'                     => 30000.00,
            'incentive_sunday_roster'          => 0,
            'incentive_home_visit'             => 500.00,
            'incentive_speech_therapy'         => 0,
            'incentive_dry_needling'           => 0,
            'allowance_allied_health_council'  => 0,
            'allowance_house_job'              => 0,
            'allowance_conveyance'             => 1000.00,
            'allowance_medical'                => 500.00,
            'allowance_house_rent'             => 0,
            'allowance_branch_manager'         => 0,
            'allowance_assistant_branch_manager' => 0,
            'other_allowance'                  => 0,
            'other_allowance_label'            => null,
            'other_allowances'                 => null,
            'joining_date'                     => now()->subYear()->toDateString(),
            'created_at'                       => now(),
            'updated_at'                       => now(),
        ], $overrides));

        return Employee::find($id);
    }

    private function createUser(): User
    {
        return User::create([
            'name'     => 'HR Admin',
            'email'    => 'hr@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    // ── service tests ─────────────────────────────────────────────────────────

    public function test_store_creates_increment_record(): void
    {
        $employee = $this->createEmployee();
        $user     = $this->createUser();
        $service  = new SalaryIncrementService();

        $increment = $service->store($employee, [
            'increment_type' => 'percentage',
            'percentage'     => 10,
            'effective_from' => now()->toDateString(),
            'reason'         => 'Annual review',
        ], $user);

        $this->assertInstanceOf(SalaryIncrement::class, $increment);
        $this->assertEquals($employee->id, $increment->employee_id);
        $this->assertEquals('percentage', $increment->increment_type);
        $this->assertEquals('Annual review', $increment->reason);
    }

    public function test_percentage_increment_scales_all_numeric_components(): void
    {
        $employee = $this->createEmployee([
            'basic_salary'      => 30000.00,
            'allowance_medical' => 500.00,
            'allowance_conveyance' => 1000.00,
            'incentive_home_visit' => 500.00,
        ]);
        $user    = $this->createUser();
        $service = new SalaryIncrementService();

        $increment = $service->store($employee, [
            'increment_type' => 'percentage',
            'percentage'     => 10,
            'effective_from' => now()->toDateString(),
        ], $user);

        $newSnapshot = $increment->new_snapshot;
        $this->assertEqualsWithDelta(33000.00, (float) $newSnapshot['basic_salary'], 0.01);
        $this->assertEqualsWithDelta(550.00,   (float) $newSnapshot['allowance_medical'], 0.01);
        $this->assertEqualsWithDelta(1100.00,  (float) $newSnapshot['allowance_conveyance'], 0.01);
        $this->assertEqualsWithDelta(550.00,   (float) $newSnapshot['incentive_home_visit'], 0.01);
    }

    public function test_store_updates_employee_basic_salary_in_database(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 30000.00]);
        $user     = $this->createUser();
        $service  = new SalaryIncrementService();

        $service->store($employee, [
            'increment_type' => 'percentage',
            'percentage'     => 10,
            'effective_from' => now()->toDateString(),
        ], $user);

        $fresh = DB::table('employees')->where('id', $employee->id)->first();
        $this->assertEqualsWithDelta(33000.00, (float) $fresh->basic_salary, 0.01);
    }

    public function test_fixed_increment_sets_explicit_new_values(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 30000.00]);
        $user     = $this->createUser();
        $service  = new SalaryIncrementService();

        $service->store($employee, [
            'increment_type'    => 'fixed',
            'basic_salary'      => 35000.00,
            'allowance_medical' => 1000.00,
            'allowance_conveyance' => 2000.00,
            // other SALARY_NUMERIC_FIELDS fields omitted → treated as 0
            'incentive_sunday_roster'          => 0,
            'incentive_home_visit'             => 0,
            'incentive_speech_therapy'         => 0,
            'incentive_dry_needling'           => 0,
            'allowance_allied_health_council'  => 0,
            'allowance_house_job'              => 0,
            'allowance_house_rent'             => 0,
            'allowance_branch_manager'         => 0,
            'allowance_assistant_branch_manager' => 0,
            'other_allowance'                  => 0,
            'effective_from' => now()->toDateString(),
        ], $user);

        $fresh = DB::table('employees')->where('id', $employee->id)->first();
        $this->assertEqualsWithDelta(35000.00, (float) $fresh->basic_salary, 0.01);
        $this->assertEqualsWithDelta(1000.00,  (float) $fresh->allowance_medical, 0.01);
    }

    public function test_increment_amount_is_net_gross_difference(): void
    {
        $employee = $this->createEmployee([
            'basic_salary'      => 30000.00,
            'allowance_medical' => 500.00,
        ]);
        $user    = $this->createUser();
        $service = new SalaryIncrementService();

        // Gross before = 30000 + 500 + 1000(conveyance) = 31500
        // 10% increase → new gross = 34650 → delta = 3150
        $increment = $service->store($employee, [
            'increment_type' => 'percentage',
            'percentage'     => 10,
            'effective_from' => now()->toDateString(),
        ], $user);

        $expectedPrevGross = SalaryIncrement::grossFromSnapshot($increment->previous_snapshot);
        $expectedNewGross  = SalaryIncrement::grossFromSnapshot($increment->new_snapshot);
        $expectedDelta     = round($expectedNewGross - $expectedPrevGross, 2);

        $this->assertEqualsWithDelta($expectedDelta, (float) $increment->increment_amount, 0.01);
    }

    // ── salaryAsOf tests ──────────────────────────────────────────────────────

    public function test_salary_as_of_returns_null_when_no_increments(): void
    {
        $employee = $this->createEmployee();
        $result   = $employee->salaryAsOf(Carbon::today());
        $this->assertNull($result);
    }

    public function test_salary_as_of_returns_correct_snapshot_for_date(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 20000.00]);
        $user     = $this->createUser();
        $service  = new SalaryIncrementService();

        // First increment two months ago
        $service->store($employee, [
            'increment_type' => 'fixed',
            'basic_salary'   => 25000.00,
            'allowance_medical' => 0, 'allowance_conveyance' => 0, 'allowance_house_rent' => 0,
            'allowance_allied_health_council' => 0, 'allowance_house_job' => 0,
            'allowance_branch_manager' => 0, 'allowance_assistant_branch_manager' => 0,
            'incentive_sunday_roster' => 0, 'incentive_home_visit' => 0,
            'incentive_speech_therapy' => 0, 'incentive_dry_needling' => 0,
            'other_allowance' => 0,
            'effective_from' => Carbon::today()->subMonths(2)->toDateString(),
        ], $user);

        // Reload to get fresh snapshot
        $employee = Employee::find($employee->id);

        // Second increment today
        $service->store($employee, [
            'increment_type' => 'fixed',
            'basic_salary'   => 30000.00,
            'allowance_medical' => 0, 'allowance_conveyance' => 0, 'allowance_house_rent' => 0,
            'allowance_allied_health_council' => 0, 'allowance_house_job' => 0,
            'allowance_branch_manager' => 0, 'allowance_assistant_branch_manager' => 0,
            'incentive_sunday_roster' => 0, 'incentive_home_visit' => 0,
            'incentive_speech_therapy' => 0, 'incentive_dry_needling' => 0,
            'other_allowance' => 0,
            'effective_from' => Carbon::today()->toDateString(),
        ], $user);

        $employee = Employee::find($employee->id);

        // As of three months ago: no increment yet → null
        $this->assertNull($employee->salaryAsOf(Carbon::today()->subMonths(3)));

        // As of one month ago: first increment applies
        $snapshotMonth1 = $employee->salaryAsOf(Carbon::today()->subMonth());
        $this->assertNotNull($snapshotMonth1);
        $this->assertEqualsWithDelta(25000.00, (float) $snapshotMonth1['basic_salary'], 0.01);

        // As of today: second increment applies
        $snapshotToday = $employee->salaryAsOf(Carbon::today());
        $this->assertNotNull($snapshotToday);
        $this->assertEqualsWithDelta(30000.00, (float) $snapshotToday['basic_salary'], 0.01);
    }

    // ── delete recalculation tests ────────────────────────────────────────────

    public function test_deleting_latest_increment_reverts_employee_salary(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 20000.00]);
        $user     = $this->createUser();
        $service  = new SalaryIncrementService();

        $increment = $service->store($employee, [
            'increment_type' => 'percentage',
            'percentage'     => 10,
            'effective_from' => now()->toDateString(),
        ], $user);

        // Employee salary is now 22000
        $service->delete($increment);

        $fresh = DB::table('employees')->where('id', $employee->id)->first();
        $this->assertEqualsWithDelta(20000.00, (float) $fresh->basic_salary, 0.01);
    }

    public function test_deleting_non_latest_increment_does_not_change_employee_salary(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 20000.00]);
        $user     = $this->createUser();
        $service  = new SalaryIncrementService();

        $first = $service->store($employee, [
            'increment_type' => 'percentage',
            'percentage'     => 10,
            'effective_from' => now()->subMonth()->toDateString(),
        ], $user);

        $employee = Employee::find($employee->id);

        $service->store($employee, [
            'increment_type' => 'percentage',
            'percentage'     => 10,
            'effective_from' => now()->toDateString(),
        ], $user);

        // Salary is now 24200 (22000 * 1.1). Deleting the first should not change current.
        $service->delete($first);

        $fresh = DB::table('employees')->where('id', $employee->id)->first();
        // Second increment's new_snapshot should still be applied (i.e. ~24200)
        $this->assertGreaterThan(22000, (float) $fresh->basic_salary);
    }

    // ── validation tests ──────────────────────────────────────────────────────

    public function test_future_effective_from_is_rejected(): void
    {
        $request   = new StoreSalaryIncrementRequest();
        $validator = Validator::make([
            'increment_type' => 'percentage',
            'percentage'     => 10,
            'effective_from' => Carbon::tomorrow()->toDateString(),
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('effective_from', $validator->errors()->toArray());
    }

    public function test_percentage_is_required_when_type_is_percentage(): void
    {
        $request   = new StoreSalaryIncrementRequest();
        $validator = Validator::make([
            'increment_type' => 'percentage',
            'effective_from' => now()->toDateString(),
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('percentage', $validator->errors()->toArray());
    }

    public function test_invalid_increment_type_is_rejected(): void
    {
        $request   = new StoreSalaryIncrementRequest();
        $validator = Validator::make([
            'increment_type' => 'magic',
            'effective_from' => now()->toDateString(),
            'percentage'     => 10,
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('increment_type', $validator->errors()->toArray());
    }

    public function test_soft_deleted_increment_is_excluded_from_salary_as_of(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 20000.00]);
        $user     = $this->createUser();
        $service  = new SalaryIncrementService();

        $increment = $service->store($employee, [
            'increment_type' => 'percentage',
            'percentage'     => 10,
            'effective_from' => now()->toDateString(),
        ], $user);

        $service->delete($increment);

        $employee = Employee::find($employee->id);
        $this->assertNull($employee->salaryAsOf(Carbon::today()));
    }
}
