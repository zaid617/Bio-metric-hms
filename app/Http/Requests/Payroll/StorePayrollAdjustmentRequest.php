<?php

namespace App\Http\Requests\Payroll;

use App\Modules\Payroll\Types\PayrollAdjustmentType;
use App\Modules\Payroll\Types\PayrollDeductionType;
use App\Modules\Payroll\Types\PayrollEarningType;
use App\Modules\Payroll\Types\PayrollAwardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'     => ['required', 'exists:employees,id'],
            'month'           => ['required', 'integer', 'min:1', 'max:12'],
            'year'            => ['required', 'integer', 'min:2020', 'max:2100'],
            'adjustment_type' => ['required', Rule::in(PayrollAdjustmentType::ALL)],
            'code'            => ['required', 'string', 'max:80'],
            'title'           => ['nullable', 'string', 'max:150'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'reason'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required'     => 'Please select an employee.',
            'employee_id.exists'       => 'The selected employee does not exist.',
            'month.required'           => 'Please select a month.',
            'year.required'            => 'Please select a year.',
            'adjustment_type.required' => 'Please select an adjustment type.',
            'adjustment_type.in'       => 'Adjustment type must be earning, deduction, or award.',
            'code.required'            => 'Please select or enter a code.',
            'amount.required'          => 'Please enter an amount.',
            'amount.min'               => 'Amount must be greater than zero.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = (string) $this->input('adjustment_type');
            $code = strtoupper((string) $this->input('code'));

            if ($type === PayrollAdjustmentType::EARNING && $code === PayrollEarningType::OVERTIME) {
                $validator->errors()->add('code', 'Overtime is calculated automatically from attendance and cannot be added manually.');
            }
        });
    }

    /**
     * Return typed codes grouped by adjustment_type for frontend use.
     */
    public static function availableCodes(): array
    {
        return [
            PayrollAdjustmentType::EARNING => [
                PayrollEarningType::ADDITIONAL_SALARY       => 'Additional Salary',
                PayrollEarningType::TREATMENT_EXTENSION_COMMISSION => 'Treatment Extension Commission',
                PayrollEarningType::SATISFACTION_BONUS      => 'Patient Satisfaction Bonus',
                PayrollEarningType::ASSESSMENT_BONUS        => 'Staff Assessment Incentive',
                PayrollEarningType::REFERENCE_BONUS         => 'Patient Reference Reward',
                PayrollEarningType::PERSONAL_PATIENT_COMMISSION => 'Personal Patient Commission',
                PayrollEarningType::CUSTOM                  => 'Custom Earning',
            ],
            PayrollAdjustmentType::DEDUCTION => [
                PayrollDeductionType::SESSION_NUMBER_MISSING  => 'Missing Session Number',
                PayrollDeductionType::WRONG_EMR_NUMBER        => 'Wrong EMR Number',
                PayrollDeductionType::TIME_MISSING            => 'Missing Time Entry',
                PayrollDeductionType::WRONG_PATIENT_NAME      => 'Wrong Patient Name',
                PayrollDeductionType::ABSENT                  => 'Absence Deduction',
                PayrollDeductionType::LATE_COMING             => 'Late Coming Deduction',
                PayrollDeductionType::ADVANCE_SALARY_DEDUCTION => 'Advance Salary Deduction',
                PayrollDeductionType::NO_SCRUB                => 'Not Wearing Scrub',
                PayrollDeductionType::NO_ID_CARD              => 'Not Wearing ID Card',
                PayrollDeductionType::LATE_UPDATE             => 'Late Record Update',
                PayrollDeductionType::CUSTOM                  => 'Custom Deduction',
            ],
            PayrollAdjustmentType::AWARD => [
                PayrollAwardType::PUNCTUALITY_AWARD => 'Punctuality Award',
                PayrollAwardType::CUSTOM            => 'Custom Award',
            ],
        ];
    }
}
