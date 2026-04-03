<?php

namespace Tests\Feature;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class EmployeeSalaryComponentsValidationTest extends TestCase
{
    use RefreshDatabase;

    private function basePayload(array $overrides = []): array
    {
        $branchId = DB::table('branches')->insertGetId([
            'name' => 'Main Branch',
            'prefix' => 'MB',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return array_merge([
            'prefix' => 'Mr.',
            'name' => 'Test Employee',
            'designation' => 'Employee',
            'branch_id' => $branchId,
            'department' => 'Male Physiotherapy Department',
            'shift' => 'Morning',
            'shift_start_time' => '09:00',
            'basic_salary' => 50000,
            'working_hours' => 8,
            'phone' => '1234567890',
            'joining_date' => now()->toDateString(),
            'allowance_allied_health_council' => 0,
            'allowance_house_job' => 0,
            'allowance_conveyance' => 0,
            'allowance_medical' => 0,
            'allowance_house_rent' => 0,
            'other_allowance' => 0,
            'other_allowance_label' => null,
        ], $overrides);
    }

    public function test_salary_component_fields_reject_negative_values(): void
    {
        $request = new StoreEmployeeRequest();
        $payload = $this->basePayload([
            'allowance_medical' => -1,
        ]);

        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('allowance_medical', $validator->errors()->toArray());
    }

    public function test_other_allowance_label_is_limited_to_255_characters(): void
    {
        $request = new StoreEmployeeRequest();
        $payload = $this->basePayload([
            'other_allowance_label' => str_repeat('x', 256),
        ]);

        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('other_allowance_label', $validator->errors()->toArray());
    }

    public function test_salary_component_fields_accept_nullable_or_zero_values(): void
    {
        $request = new StoreEmployeeRequest();
        $payload = $this->basePayload([
            'allowance_house_job' => 0,
            'other_allowance' => null,
            'other_allowance_label' => null,
        ]);

        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }
}
