<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollSetting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayrollSettingController extends Controller
{
    public function index()
    {
        try {
            $settings = PayrollSetting::current();
            return view('payroll.settings', compact('settings'));
        } catch (Exception $e) {
            Log::error('Payroll settings load failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load payroll settings.');
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'default_shift_hours'             => 'required|numeric|min:1|max:24',
            'overtime_multiplier'             => 'required|numeric|min:1|max:5',
            'work_on_saturday'                => 'nullable|boolean',
            'shift_start'                     => 'required|date_format:H:i',
            'late_grace_minutes'              => 'required|integer|min:0|max:120',
            'treatment_extension_commission'  => 'required|numeric|min:0|max:1',
            'assessment_incentive'            => 'required|numeric|min:0|max:1',
            'personal_patient_commission'     => 'required|numeric|min:0|max:1',
            'satisfactory_session_amount'     => 'required|numeric|min:0',
            'satisfaction_threshold'          => 'required|integer|min:0|max:100',
            'satisfaction_bonus_per_feedback' => 'required|numeric|min:0',
            'reference_bonus_per_patient'     => 'required|numeric|min:0',
            'punctuality_amount'              => 'required|numeric|min:0',
            'absent_per_day'                  => 'required|numeric|min:0',
            'late_per_day'                    => 'required|numeric|min:0',
        ]);

        try {
            // Checkboxes come as null when unchecked
            $validated['work_on_saturday'] = $request->boolean('work_on_saturday');

            $settings = PayrollSetting::current();
            $settings->update($validated);

            // Refresh the runtime config so the rest of the request (and future
            // requests that boot via AppServiceProvider) picks up the new values.
            config(['payroll' => $settings->fresh()->toConfigArray()]);

            return redirect()->back()->with('success', 'Payroll settings updated successfully.');
        } catch (Exception $e) {
            Log::error('Payroll settings update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update payroll settings. Please try again.');
        }
    }
}
