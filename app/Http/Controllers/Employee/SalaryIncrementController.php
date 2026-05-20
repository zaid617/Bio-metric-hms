<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreSalaryIncrementRequest;
use App\Http\Requests\Employee\UpdateSalaryIncrementRequest;
use App\Models\Employee;
use App\Models\SalaryIncrement;
use App\Services\Employee\SalaryIncrementService;

class SalaryIncrementController extends Controller
{
    public function __construct(private readonly SalaryIncrementService $service) {}

    public function store(StoreSalaryIncrementRequest $request, Employee $employee)
    {
        try {
            $this->service->store($employee, $request->validated(), auth()->user());

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Salary increment recorded successfully.');
        } catch (\Exception $e) {
            \Log::error('Salary increment store error: ' . $e->getMessage());

            return back()
                ->with('error', 'Unable to record salary increment. Please try again.')
                ->withInput();
        }
    }

    public function update(UpdateSalaryIncrementRequest $request, Employee $employee, SalaryIncrement $increment)
    {
        abort_if($increment->employee_id !== $employee->id, 403);

        try {
            $this->service->update($increment, $request->validated());

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Salary increment updated.');
        } catch (\Exception $e) {
            \Log::error('Salary increment update error: ' . $e->getMessage());

            return back()
                ->with('error', 'Unable to update increment.')
                ->withInput();
        }
    }

    public function destroy(Employee $employee, SalaryIncrement $increment)
    {
        abort_if($increment->employee_id !== $employee->id, 403);

        try {
            $this->service->delete($increment);

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Salary increment deleted and employee salary recalculated.');
        } catch (\Exception $e) {
            \Log::error('Salary increment delete error: ' . $e->getMessage());

            return back()->with('error', 'Unable to delete increment.');
        }
    }
}
