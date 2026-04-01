<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;

class AttendanceRecordController extends Controller
{
    /**
     * Display attendance records
     */
    public function index(Request $request)
    {
        $query = AttendanceRecord::with(['employee', 'branch', 'device']);

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by employee
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('attendance_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('attendance_date', '<=', $request->date_to);
        } else {
            // Default to current month if no date filter
            $query->whereMonth('attendance_date', Carbon::now()->month)
                ->whereYear('attendance_date', Carbon::now()->year);
        }

        $records = $query->latest('attendance_date')->paginate(10);

        // Get branches and employees for filters
        $branches = Branch::where('status', 'active')->get();
        $employees = Employee::select('id', 'name', 'designation')->orderBy('name')->get();

        return view('attendance.records.index', compact('records', 'branches', 'employees'));
    }

    /**
     * Show single day attendance detail for an employee
     */
    public function show(Employee $employee, $date)
    {
        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->where('attendance_date', $date)
            ->with(['checkInRawLog', 'checkOutRawLog', 'device', 'branch'])
            ->firstOrFail();

        return view('attendance.records.show', compact('record', 'employee'));
    }

    /**
     * Show form for manual adjustment
     */
    public function edit(AttendanceRecord $record)
    {
        return view('attendance.records.edit', compact('record'));
    }

    /**
     * Update attendance record (manual adjustment)
     */
    public function update(Request $request, AttendanceRecord $record)
    {
        $validated = $request->validate([
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,absent,late,half_day,leave,holiday,weekend',
            'admin_note' => 'nullable|string|max:500',
        ]);

        try {
            $validated['is_manually_adjusted'] = true;
            $validated['adjusted_by'] = Auth::id();
            $validated['adjusted_at'] = now();

            // Recalculate working minutes if both check-in and check-out are present
            if ($validated['check_in'] && $validated['check_out']) {
                $checkIn = Carbon::parse($record->attendance_date . ' ' . $validated['check_in']);
                $checkOut = Carbon::parse($record->attendance_date . ' ' . $validated['check_out']);

                if ($checkOut->lt($checkIn)) {
                    $checkOut->addDay();
                }

                $workingMinutes = $checkIn->diffInMinutes($checkOut);
                $validated['total_working_minutes'] = $workingMinutes;

                $standardMinutes = (float) ($record->employee?->working_hours ?? config('payroll.default_shift_hours', 8)) * 60;
                $validated['overtime_minutes'] = max(0, (int) ($workingMinutes - $standardMinutes));

                if (in_array($validated['status'], ['present', 'late'], true)) {
                    $shiftStart = $record->employee?->shift_start_time
                        ? substr((string) $record->employee->shift_start_time, 0, 5)
                        : config('payroll.shift_start', '09:00');
                    $graceMinutes = (int) config('payroll.late_grace_minutes', 15);
                    $deadline = Carbon::parse($record->attendance_date . ' ' . $shiftStart)->addMinutes($graceMinutes);
                    $validated['status'] = $checkIn->gt($deadline) ? 'late' : 'present';
                }
            }

            $record->update($validated);

            return redirect()->route('attendance.records.index')
                ->with('success', 'Attendance record updated successfully!');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Update failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mark missing checkout
     */
    public function markMissingCheckout(AttendanceRecord $record)
    {
        $record->update(['is_checkout_missing' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Marked as missing checkout',
        ]);
    }

    /**
     * Apply manual checkout time
     */
    public function applyManualCheckout(Request $request, AttendanceRecord $record)
    {
        $request->validate([
            'checkout_time' => 'required|date_format:H:i',
        ]);

        try {
            $record->update([
                'check_out' => $request->checkout_time,
                'is_checkout_missing' => false,
                'is_manually_adjusted' => true,
                'adjusted_by' => Auth::id(),
                'adjusted_at' => now(),
            ]);

            // Recalculate working minutes
            if ($record->check_in) {
                $checkIn = Carbon::parse($record->attendance_date . ' ' . $record->check_in);
                $checkOut = Carbon::parse($record->attendance_date . ' ' . $request->checkout_time);

                if ($checkOut->lt($checkIn)) {
                    $checkOut->addDay();
                }

                $workingMinutes = $checkIn->diffInMinutes($checkOut);
                $standardMinutes = (float) ($record->employee?->working_hours ?? config('payroll.default_shift_hours', 8)) * 60;

                $shiftStart = $record->employee?->shift_start_time
                    ? substr((string) $record->employee->shift_start_time, 0, 5)
                    : config('payroll.shift_start', '09:00');
                $graceMinutes = (int) config('payroll.late_grace_minutes', 15);
                $deadline = Carbon::parse($record->attendance_date . ' ' . $shiftStart)->addMinutes($graceMinutes);
                $status = in_array($record->status, ['present', 'late'], true)
                    ? ($checkIn->gt($deadline) ? 'late' : 'present')
                    : $record->status;

                $record->update([
                    'total_working_minutes' => $workingMinutes,
                    'overtime_minutes' => max(0, (int) ($workingMinutes - $standardMinutes)),
                    'status' => $status,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Manual checkout applied successfully!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply checkout: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export records (placeholder)
     */
    public function export(Request $request)
    {
        // TODO: Implement CSV/Excel export
        return redirect()->back()->with('info', 'Export functionality coming soon!');
    }
}
