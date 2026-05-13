<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::orderBy('name')->get();

        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        return view('departments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        Department::create($validated);

        return redirect()->route('departments.index')->with('success', 'Department added successfully.');
    }

    public function edit($id)
    {
        $department = Department::findOrFail($id);

        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $department->update($validated);

        return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);

        $isInUse = Employee::where('department_id', $department->id)
            ->orWhere('department', $department->name)
            ->exists();

        if ($isInUse) {
            return back()->with('error', 'This department cannot be deleted because employees are assigned to it.');
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted successfully.');
    }
}
