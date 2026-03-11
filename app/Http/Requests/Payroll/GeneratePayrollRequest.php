<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level middleware handles authorization
    }

    public function rules(): array
    {
        return [
            'period_start'     => ['required', 'date'],
            'branch_id'        => ['nullable', 'exists:branches,id'],
            'employee_id'      => ['nullable', 'exists:employees,id'],
            'force_regenerate' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_start.required' => 'Please select a payroll month.',
            'period_start.date'     => 'Payroll month must be a valid date.',
            'branch_id.exists'      => 'The selected branch does not exist.',
            'employee_id.exists'    => 'The selected employee does not exist.',
        ];
    }
}
