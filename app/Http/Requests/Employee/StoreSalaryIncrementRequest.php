<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use App\Models\SalaryIncrement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryIncrementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware handles permission
    }

    public function rules(): array
    {
        $isFixed = $this->input('increment_type') === 'fixed';

        $rules = [
            'increment_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'effective_from' => ['required', 'date', 'before_or_equal:today'],
            'reason'         => ['nullable', 'string', 'max:1000'],
            'percentage'     => [
                $isFixed ? 'nullable' : 'required',
                'numeric', 'gt:0', 'max:1000',
            ],
        ];

        if ($isFixed) {
            foreach (Employee::SALARY_NUMERIC_FIELDS as $field) {
                $rules[$field] = ['required', 'numeric', 'min:0'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'increment_type.required' => 'Please select an increment type.',
            'increment_type.in'       => 'Increment type must be fixed or percentage.',
            'effective_from.required' => 'Please enter the effective date.',
            'effective_from.before_or_equal' => 'Effective date cannot be in the future.',
            'percentage.required'     => 'Please enter the percentage increase.',
            'percentage.gt'           => 'Percentage must be greater than zero.',
            'basic_salary.required'   => 'Basic salary is required.',
            'basic_salary.min'        => 'Basic salary cannot be negative.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var \App\Models\Employee|null $employee */
            $employee = $this->route('employee');

            if (!$employee instanceof Employee) {
                return;
            }

            $date = $this->input('effective_from');
            if (!$date) {
                return;
            }

            $duplicate = SalaryIncrement::where('employee_id', $employee->id)
                ->where('effective_from', $date)
                ->exists();

            if ($duplicate) {
                $validator->errors()->add(
                    'effective_from',
                    'An increment with this effective date already exists for this employee.'
                );
            }
        });
    }
}
