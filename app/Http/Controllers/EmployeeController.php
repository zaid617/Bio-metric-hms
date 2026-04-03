<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    // INDEX - Employee List
    public function index()
    {
        try {
            $employees = DB::table('employees')
                ->join('branches', 'employees.branch_id', '=', 'branches.id')
                ->select('employees.*', 'branches.name as branch_name')
                ->get();

            return view('employees.index', compact('employees'));
        } catch (\Exception $e) {
            \Log::error('Employee index error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load employees list.');
        }
    }

    // CREATE - Show form
    public function create()
    {
        try {
            $branches = DB::table('branches')->get();
            return view('employees.create', compact('branches'));
        } catch (\Exception $e) {
            \Log::error('Employee create form error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load employee creation form.');
        }
    }

    // STORE - Save Employee
    public function store(StoreEmployeeRequest $request)
    {
        try {
            $validated = $request->validated();

            DB::transaction(function () use ($validated) {
                DB::table('employees')->insert([
                    'prefix' => $validated['prefix'],
                    'name' => $validated['name'],
                    'designation' => $validated['designation'],
                    'branch_id' => $validated['branch_id'],
                    'department' => $validated['department'],
                    'shift' => $validated['shift'],
                    'shift_start_time' => $validated['shift_start_time'],
                    'basic_salary' => (float) str_replace(',', '', (string) $validated['basic_salary']),
                    'allowance_allied_health_council' => (float) ($validated['allowance_allied_health_council'] ?? 0),
                    'allowance_house_job' => (float) ($validated['allowance_house_job'] ?? 0),
                    'allowance_conveyance' => (float) ($validated['allowance_conveyance'] ?? 0),
                    'allowance_medical' => (float) ($validated['allowance_medical'] ?? 0),
                    'allowance_house_rent' => (float) ($validated['allowance_house_rent'] ?? 0),
                    'other_allowance' => (float) ($validated['other_allowance'] ?? 0),
                    'other_allowance_label' => $validated['other_allowance_label'] ?? null,
                    'working_hours' => $validated['working_hours'],
                    'phone' => $validated['phone'],
                    'joining_date' => $validated['joining_date'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            return redirect('employees')->with('success', 'Employee added successfully!');
        } catch (\Exception $e) {
            \Log::error('Employee store error: ' . $e->getMessage());
            return back()->with('error', 'Unable to add employee. Please try again.')->withInput();
        }
    }

    // EDIT - Show form with data
    public function edit($id)
    {
        try {
            $employee = DB::table('employees')->where('id', $id)->first();
            $branches = DB::table('branches')->get();

            if (!$employee) {
                return redirect('employees')->with('error', 'Employee not found.');
            }

            return view('employees.edit', compact('employee', 'branches'));
        } catch (\Exception $e) {
            \Log::error('Employee edit error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load edit form.');
        }
    }

    // UPDATE - Save changes
    public function update(UpdateEmployeeRequest $request, $id)
    {
        try {
            $validated = $request->validated();

            DB::transaction(function () use ($validated, $id) {
                DB::table('employees')->where('id', $id)->update([
                    'prefix' => $validated['prefix'],
                    'name' => $validated['name'],
                    'designation' => $validated['designation'],
                    'branch_id' => $validated['branch_id'],
                    'department' => $validated['department'],
                    'shift' => $validated['shift'],
                    'shift_start_time' => $validated['shift_start_time'],
                    'basic_salary' => (float) str_replace(',', '', (string) $validated['basic_salary']),
                    'allowance_allied_health_council' => (float) ($validated['allowance_allied_health_council'] ?? 0),
                    'allowance_house_job' => (float) ($validated['allowance_house_job'] ?? 0),
                    'allowance_conveyance' => (float) ($validated['allowance_conveyance'] ?? 0),
                    'allowance_medical' => (float) ($validated['allowance_medical'] ?? 0),
                    'allowance_house_rent' => (float) ($validated['allowance_house_rent'] ?? 0),
                    'other_allowance' => (float) ($validated['other_allowance'] ?? 0),
                    'other_allowance_label' => $validated['other_allowance_label'] ?? null,
                    'working_hours' => $validated['working_hours'],
                    'phone' => $validated['phone'],
                    'joining_date' => $validated['joining_date'],
                    'updated_at' => now(),
                ]);
            });

            return redirect('employees')->with('success', 'Employee updated successfully!');
        } catch (\Exception $e) {
            \Log::error('Employee update error: ' . $e->getMessage());
            return back()->with('error', 'Unable to update employee. Please try again.')->withInput();
        }
    }

    // DESTROY - Delete Employee
    public function destroy($id)
    {
        try {
            DB::table('employees')->where('id', $id)->delete();
            return redirect('employees')->with('success', 'Employee deleted successfully!');
        } catch (\Exception $e) {
            \Log::error('Employee delete error: ' . $e->getMessage());
            return back()->with('error', 'Unable to delete employee.');
        }
    }
}
