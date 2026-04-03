<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prefix' => 'required|string|in:Mr.,Ms.,Mrs.',
            'name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'branch_id' => 'required|integer|exists:branches,id',
            'department' => 'required|string|in:Male Physiotherapy Department,Female Physiotherapy Department,Paeds Physiotherapy Department,Speech Therapy Department,Behavior Therapy Department,Occupational Therapy Department,Remedial Therapy Department,Clinical Psychology Department',
            'shift' => 'required|string|in:Morning,Afternoon,Evening',
            'shift_start_time' => 'required|date_format:H:i',
            'basic_salary' => 'required|numeric|min:0',
            'working_hours' => 'required|numeric|min:1|max:24',
            'phone' => 'required|string|max:20',
            'joining_date' => 'required|date',

            'allowance_allied_health_council' => 'nullable|numeric|min:0',
            'allowance_house_job' => 'nullable|numeric|min:0',
            'allowance_conveyance' => 'nullable|numeric|min:0',
            'allowance_medical' => 'nullable|numeric|min:0',
            'allowance_house_rent' => 'nullable|numeric|min:0',
            'incentive_sunday_roster' => 'nullable|numeric|min:0',
            'incentive_home_visit' => 'nullable|numeric|min:0',
            'incentive_speech_therapy' => 'nullable|numeric|min:0',
            'incentive_dry_needling' => 'nullable|numeric|min:0',

            'other_allowance' => 'nullable|numeric|min:0',
            'other_allowance_label' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'basic_salary.numeric' => 'Basic salary must be a valid number.',
            'allowance_allied_health_council.numeric' => 'Allied health council allowance must be a valid number.',
            'allowance_house_job.numeric' => 'House job allowance must be a valid number.',
            'allowance_conveyance.numeric' => 'Conveyance allowance must be a valid number.',
            'allowance_medical.numeric' => 'Medical allowance must be a valid number.',
            'allowance_house_rent.numeric' => 'House rent allowance must be a valid number.',
            'incentive_sunday_roster.numeric' => 'Sunday roster incentive must be a valid number.',
            'incentive_home_visit.numeric' => 'Home visit incentive must be a valid number.',
            'incentive_speech_therapy.numeric' => 'Speech therapy incentive must be a valid number.',
            'incentive_dry_needling.numeric' => 'Dry needling incentive must be a valid number.',
            'other_allowance.numeric' => 'Other allowance must be a valid number.',
            'other_allowance_label.max' => 'Other allowance label may not be greater than 255 characters.',
        ];
    }
}
