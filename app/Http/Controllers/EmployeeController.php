<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Services\Attendance\AttendanceSyncService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function __construct(private readonly AttendanceSyncService $attendanceSyncService)
    {
    }

    // INDEX - Employee List
    public function index()
    {
        try {
            $query = DB::table('employees')
                ->join('branches', 'employees.branch_id', '=', 'branches.id')
                ->select('employees.*', 'branches.name as branch_name');

            $user = auth()->user();

            if (!user_can_manage_all_branches($user)) {
                $query->where('employees.branch_id', user_branch_id($user));
            }

            // Apply Filters
            if (request('branch')) {
                $query->where('employees.branch_id', request('branch'));
            }
            if (request('designation')) {
                $query->where('employees.designation', request('designation'));
            }
            if (request('search')) {
                $search = trim((string) request('search'));

                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('employees.prefix', 'like', '%' . $search . '%')
                        ->orWhere('employees.name', 'like', '%' . $search . '%')
                        ->orWhere('employees.designation', 'like', '%' . $search . '%')
                        ->orWhere('employees.department', 'like', '%' . $search . '%')
                        ->orWhere('employees.shift', 'like', '%' . $search . '%')
                        ->orWhere('employees.phone', 'like', '%' . $search . '%')
                        ->orWhere('branches.name', 'like', '%' . $search . '%');
                });
            }

            $employees = $query->get();

            // Get filter options
            $branches = DB::table('branches')->orderBy('name')->get();
            $designations = DB::table('employees')
                ->distinct()
                ->pluck('designation')
                ->filter()
                ->sort();

            return view('employees.index', compact('employees', 'branches', 'designations'));
        } catch (\Exception $e) {
            \Log::error('Employee index error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load employees list.');
        }
    }

    // CREATE - Show form
    public function create()
    {
        try {
            $user = auth()->user();
            $branches = user_can_manage_all_branches($user)
                ? DB::table('branches')->get()
                : DB::table('branches')->where('id', user_branch_id($user))->get();
            $departments = Department::orderBy('name')->get();
            return view('employees.create', compact('branches', 'departments'));
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
            $appointmentLetterPath = $this->storeAppointmentLetter($request->file('appointment_letter'));

            if (!user_can_manage_all_branches(auth()->user())) {
                $validated['branch_id'] = user_branch_id();
            }
            $otherAllowances = $this->normalizeOtherAllowances($validated);
            $totalOtherAllowance = round((float) collect($otherAllowances)->sum('amount'), 2);
            $otherAllowanceLabel = count($otherAllowances) === 1
                ? ($otherAllowances[0]['label'] ?? 'Other Allowance')
                : null;

            DB::transaction(function () use ($validated, $otherAllowances, $totalOtherAllowance, $otherAllowanceLabel, $appointmentLetterPath) {
                $department = Department::findOrFail($validated['department_id']);

                DB::table('employees')->insert([
                    'prefix' => $validated['prefix'],
                    'name' => $validated['name'],
                    'designation' => $validated['designation'],
                    'branch_id' => $validated['branch_id'],
                    'department_id' => $department->id,
                    'department' => $department->name,
                    'shift' => $validated['shift'],
                    'shift_start_time' => $validated['shift_start_time'],
                    'basic_salary' => (float) str_replace(',', '', (string) $validated['basic_salary']),
                    'incentive_sunday_roster' => (float) ($validated['incentive_sunday_roster'] ?? 0),
                    'incentive_home_visit' => (float) ($validated['incentive_home_visit'] ?? 0),
                    'incentive_speech_therapy' => (float) ($validated['incentive_speech_therapy'] ?? 0),
                    'incentive_dry_needling' => (float) ($validated['incentive_dry_needling'] ?? 0),
                    'allowance_allied_health_council' => (float) ($validated['allowance_allied_health_council'] ?? 0),
                    'allowance_house_job' => (float) ($validated['allowance_house_job'] ?? 0),
                    'allowance_conveyance' => (float) ($validated['allowance_conveyance'] ?? 0),
                    'allowance_medical' => (float) ($validated['allowance_medical'] ?? 0),
                    'allowance_house_rent' => (float) ($validated['allowance_house_rent'] ?? 0),
                    'allowance_branch_manager' => (float) ($validated['allowance_branch_manager'] ?? 0),
                    'allowance_assistant_branch_manager' => (float) ($validated['allowance_assistant_branch_manager'] ?? 0),
                    'other_allowance' => $totalOtherAllowance,
                    'other_allowance_label' => $otherAllowanceLabel,
                    'other_allowances' => !empty($otherAllowances) ? json_encode($otherAllowances) : null,
                    'working_hours' => $validated['working_hours'],
                    'phone' => $validated['phone'],
                    'joining_date' => $validated['joining_date'],
                    'appointment_letter' => $appointmentLetterPath,
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
            $user = auth()->user();
            $branches = user_can_manage_all_branches($user)
                ? DB::table('branches')->get()
                : DB::table('branches')->where('id', user_branch_id($user))->get();
            $departments = Department::orderBy('name')->get();

            if (!$employee) {
                return redirect('employees')->with('error', 'Employee not found.');
            }

            $departmentId = $employee->department_id ?? Department::where('name', $employee->department)->value('id');

            return view('employees.edit', compact('employee', 'branches', 'departments', 'departmentId'));
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
            $employee = Employee::find($id);
            if (!$employee) {
                return redirect('employees')->with('error', 'Employee not found.');
            }

            $appointmentLetterPath = $employee->appointment_letter;
            if ($request->hasFile('appointment_letter')) {
                $this->deleteAppointmentLetter($employee->appointment_letter);
                $appointmentLetterPath = $this->storeAppointmentLetter($request->file('appointment_letter'));
            }

            if (!user_can_manage_all_branches(auth()->user())) {
                $validated['branch_id'] = user_branch_id();
            }
            $otherAllowances = $this->normalizeOtherAllowances($validated);
            $totalOtherAllowance = round((float) collect($otherAllowances)->sum('amount'), 2);
            $otherAllowanceLabel = count($otherAllowances) === 1
                ? ($otherAllowances[0]['label'] ?? 'Other Allowance')
                : null;

            DB::transaction(function () use ($validated, $id, $otherAllowances, $totalOtherAllowance, $otherAllowanceLabel, $appointmentLetterPath) {
                $department = Department::findOrFail($validated['department_id']);

                DB::table('employees')->where('id', $id)->update([
                    'prefix' => $validated['prefix'],
                    'name' => $validated['name'],
                    'designation' => $validated['designation'],
                    'branch_id' => $validated['branch_id'],
                    'department_id' => $department->id,
                    'department' => $department->name,
                    'shift' => $validated['shift'],
                    'shift_start_time' => $validated['shift_start_time'],
                    'basic_salary' => (float) str_replace(',', '', (string) $validated['basic_salary']),
                    'incentive_sunday_roster' => (float) ($validated['incentive_sunday_roster'] ?? 0),
                    'incentive_home_visit' => (float) ($validated['incentive_home_visit'] ?? 0),
                    'incentive_speech_therapy' => (float) ($validated['incentive_speech_therapy'] ?? 0),
                    'incentive_dry_needling' => (float) ($validated['incentive_dry_needling'] ?? 0),
                    'allowance_allied_health_council' => (float) ($validated['allowance_allied_health_council'] ?? 0),
                    'allowance_house_job' => (float) ($validated['allowance_house_job'] ?? 0),
                    'allowance_conveyance' => (float) ($validated['allowance_conveyance'] ?? 0),
                    'allowance_medical' => (float) ($validated['allowance_medical'] ?? 0),
                    'allowance_house_rent' => (float) ($validated['allowance_house_rent'] ?? 0),
                    'allowance_branch_manager' => (float) ($validated['allowance_branch_manager'] ?? 0),
                    'allowance_assistant_branch_manager' => (float) ($validated['allowance_assistant_branch_manager'] ?? 0),
                    'other_allowance' => $totalOtherAllowance,
                    'other_allowance_label' => $otherAllowanceLabel,
                    'other_allowances' => !empty($otherAllowances) ? json_encode($otherAllowances) : null,
                    'working_hours' => $validated['working_hours'],
                    'phone' => $validated['phone'],
                    'joining_date' => $validated['joining_date'],
                    'appointment_letter' => $appointmentLetterPath,
                    'updated_at' => now(),
                ]);
            });

            if ($employee) {
                $this->attendanceSyncService->recalculateEmployeeAttendanceRecords($employee);
            }

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
            $employee = Employee::find($id);
            if ($employee) {
                $this->deleteAppointmentLetter($employee->appointment_letter);
                DB::table('employees')->where('id', $id)->delete();
            }
            return redirect('employees')->with('success', 'Employee deleted successfully!');
        } catch (\Exception $e) {
            \Log::error('Employee delete error: ' . $e->getMessage());
            return back()->with('error', 'Unable to delete employee.');
        }
    }

    public function show($id)
    {
        try {
            $employee = Employee::with('branch', 'departmentRecord')->find($id);

            if (!$employee) {
                return redirect('employees')->with('error', 'Employee not found.');
            }

            $salaryIncrements = $employee->salaryIncrements()
                ->with('creator')
                ->paginate(10, ['*'], 'increment_page');

            $user = auth()->user();
            $canManageSalary = $user && (
                user_is_admin_like($user) ||
                $user->hasPermissionTo('employees.salary.manage')
            );

            return view('employees.show', compact('employee', 'salaryIncrements', 'canManageSalary'));
        } catch (\Exception $e) {
            \Log::error('Employee show error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load employee details.');
        }
    }

    private function normalizeOtherAllowances(array $validated): array
    {
        $labels = data_get($validated, 'other_allowances.labels', []);
        $amounts = data_get($validated, 'other_allowances.amounts', []);

        $labels = is_array($labels) ? array_values($labels) : [];
        $amounts = is_array($amounts) ? array_values($amounts) : [];

        $rows = [];
        $maxRows = max(count($labels), count($amounts));

        for ($i = 0; $i < $maxRows; $i++) {
            $amount = (float) ($amounts[$i] ?? 0);
            $label = trim((string) ($labels[$i] ?? ''));

            if ($amount <= 0) {
                continue;
            }

            $rows[] = [
                'label' => $label !== '' ? $label : 'Other Allowance',
                'amount' => round($amount, 2),
            ];
        }

        $legacyOtherAllowance = (float) ($validated['other_allowance'] ?? 0);
        if ($legacyOtherAllowance > 0) {
            $legacyLabel = trim((string) ($validated['other_allowance_label'] ?? ''));
            $rows[] = [
                'label' => $legacyLabel !== '' ? $legacyLabel : 'Other Allowance',
                'amount' => round($legacyOtherAllowance, 2),
            ];
        }

        return $rows;
    }

    private function storeAppointmentLetter(?UploadedFile $file): ?string
    {
        if (!$file) {
            return null;
        }

        $directory = public_path('upload/appointment_letters');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = now()->format('YmdHis') . '_' . Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'upload/appointment_letters/' . $filename;
    }

    private function deleteAppointmentLetter(?string $path): void
    {
        if (!$path) {
            return;
        }

        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }
}
