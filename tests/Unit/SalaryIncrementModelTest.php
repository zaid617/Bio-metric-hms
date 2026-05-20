<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\SalaryIncrement;
use PHPUnit\Framework\TestCase;

class SalaryIncrementModelTest extends TestCase
{
    private function makeSnapshot(array $overrides = []): array
    {
        return array_merge(array_fill_keys(Employee::SALARY_NUMERIC_FIELDS, 0.0), $overrides);
    }

    public function test_gross_from_snapshot_sums_all_numeric_fields(): void
    {
        $snapshot = $this->makeSnapshot([
            'basic_salary'       => 30000.00,
            'allowance_medical'  => 500.00,
            'allowance_conveyance' => 1000.00,
            'incentive_home_visit' => 200.00,
        ]);

        $gross = SalaryIncrement::grossFromSnapshot($snapshot);

        $this->assertEqualsWithDelta(31700.00, $gross, 0.01);
    }

    public function test_gross_from_snapshot_ignores_missing_fields(): void
    {
        $snapshot = ['basic_salary' => 20000.00];

        $gross = SalaryIncrement::grossFromSnapshot($snapshot);

        $this->assertEqualsWithDelta(20000.00, $gross, 0.01);
    }

    public function test_gross_from_snapshot_returns_zero_for_empty_snapshot(): void
    {
        $this->assertEqualsWithDelta(0.0, SalaryIncrement::grossFromSnapshot([]), 0.01);
    }

    public function test_previous_gross_accessor(): void
    {
        $increment = new SalaryIncrement();
        $increment->previous_snapshot = $this->makeSnapshot(['basic_salary' => 25000.00]);
        $increment->new_snapshot      = $this->makeSnapshot(['basic_salary' => 27500.00]);

        $this->assertEqualsWithDelta(25000.00, $increment->previous_gross, 0.01);
    }

    public function test_new_gross_accessor(): void
    {
        $increment = new SalaryIncrement();
        $increment->previous_snapshot = $this->makeSnapshot(['basic_salary' => 25000.00]);
        $increment->new_snapshot      = $this->makeSnapshot(['basic_salary' => 27500.00]);

        $this->assertEqualsWithDelta(27500.00, $increment->new_gross, 0.01);
    }

    public function test_employee_salary_numeric_fields_count(): void
    {
        // Guard that no one accidentally removes or renames a component field.
        $this->assertCount(13, Employee::SALARY_NUMERIC_FIELDS);
    }

    public function test_employee_salary_all_fields_includes_label_and_json(): void
    {
        $this->assertContains('other_allowance_label', Employee::SALARY_ALL_FIELDS);
        $this->assertContains('other_allowances', Employee::SALARY_ALL_FIELDS);
    }

    public function test_employee_apply_snapshot_sets_in_memory_values(): void
    {
        $employee = new Employee([
            'basic_salary'      => 20000,
            'allowance_medical' => 0,
        ]);

        $snapshot = $this->makeSnapshot([
            'basic_salary'      => 25000.00,
            'allowance_medical' => 1000.00,
        ]);

        $employee->applySnapshot($snapshot);

        $this->assertEquals(25000.00, (float) $employee->basic_salary);
        $this->assertEquals(1000.00,  (float) $employee->allowance_medical);
    }

    public function test_employee_apply_snapshot_does_not_alter_non_salary_fields(): void
    {
        $employee          = new Employee(['name' => 'Test', 'basic_salary' => 20000]);
        $snapshot          = $this->makeSnapshot(['basic_salary' => 30000]);
        $employee->applySnapshot($snapshot);

        $this->assertEquals('Test', $employee->name);
        $this->assertEquals(30000, (float) $employee->basic_salary);
    }
}
