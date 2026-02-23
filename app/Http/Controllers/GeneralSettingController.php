<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use App\Models\Branch;
use Exception;
use Illuminate\Support\Facades\Log;

class GeneralSettingController extends Controller
{
    public function index()
    {
        try {
            $branches = Branch::all();
            return view('settings.general', compact('branches'));
        } catch (Exception $e) {
            Log::error('General settings load failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load settings. Please try again.');
        }
    }

    public function update(Request $request)
    {
        try {
            foreach ($request->fees as $branchId => $fee) {
                GeneralSetting::updateOrCreate(
                    ['branch_id' => $branchId],
                    ['default_checkup_fee' => $fee]
                );
            }

            return redirect()->back()->with('success', 'Settings updated.');
        } catch (Exception $e) {
            Log::error('General settings update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update settings. Please try again.');
        }
    }
}
