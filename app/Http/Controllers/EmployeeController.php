<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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
    public function store(Request $request)
    {
        try {
            $request->validate([
                'prefix'       => 'required|string|in:Mr.,Ms.,Mrs.',
                'name'         => 'required|string|max:255',
                'designation'  => 'required|string|max:255',
                'branch_id'    => 'required|integer|exists:branches,id',
                'department'   => 'required|string|in:Male Physiotherapy Department,Female Physiotherapy Department,Paeds Physiotherapy Department,Speech Therapy Department,Behavior Therapy Department,Occupational Therapy Department,Remedial Therapy Department,Clinical Psychology Department',
                'shift'        => 'required|string|in:Morning,Afternoon,Evening',
                'basic_salary' => 'required',
                'phone'        => 'required|string|max:20',
                'joining_date' => 'required|date',
            ]);

            $salary = str_replace(',', '', $request->basic_salary);

            DB::table('employees')->insert([
                'prefix'       => $request->prefix,
                'name'         => $request->name,
                'designation'  => $request->designation,
                'branch_id'    => $request->branch_id,
                'department'   => $request->department,
                'shift'        => $request->shift,
                'basic_salary' => $salary,
                'phone'        => $request->phone,
                'joining_date' => $request->joining_date,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            return redirect('employees')->with('success', 'Employee added successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
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
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                 'prefix'       => 'required|string|in:Mr.,Ms.,Mrs.',
                'name'         => 'required|string|max:255',
                'designation'  => 'required|string|max:255',
                'branch_id'    => 'required|integer|exists:branches,id',
                'department'   => 'required|string|in:Male Physiotherapy Department,Female Physiotherapy Department,Paeds Physiotherapy Department,Speech Therapy Department,Behavior Therapy Department,Occupational Therapy Department,Remedial Therapy Department,Clinical Psychology Department',
                'shift'        => 'required|string|in:Morning,Afternoon,Evening',
                'basic_salary' => 'required',
                'phone'        => 'required|string|max:20',
                'joining_date' => 'required|date',
            ]);

            $salary = str_replace(',', '', $request->basic_salary);

            DB::table('employees')->where('id', $id)->update([
                 'prefix'       => $request->prefix, 
                'name'         => $request->name,
                'designation'  => $request->designation,
                'branch_id'    => $request->branch_id,
                'department'   => $request->department,
                'shift'        => $request->shift,
                'basic_salary' => $salary,
                'phone'        => $request->phone,
                'joining_date' => $request->joining_date,
                'updated_at'   => now(),
            ]);

            return redirect('employees')->with('success', 'Employee updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
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
