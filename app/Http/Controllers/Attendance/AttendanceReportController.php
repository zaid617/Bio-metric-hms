<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceReportController extends Controller
{
    /**
     * Daily attendance report
     */
    public function daily(Request $request)
    {
        $date = $request->has('date') ? Carbon::parse($request->date) : Carbon::today();
        $branchId = $request->get('branch_id');

        $query = AttendanceRecord::with(['employee', 'branch'])
            ->where('attendance_date', $date->toDateString());

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $records = $query->get();

        // Statistics
        $stats = [
            'total' => $records->count(),
            'present' => $records->whereIn('status', ['present', 'late'])->count(),
            'late' => $records->where('status', 'late')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'on_leave' => $records->where('status', 'leave')->count(),
        ];

        $branches = Branch::where('status', 'active')->get();

        return view('attendance.reports.daily', compact('date', 'records', 'stats', 'branches'));
    }

    /**
     * Monthly attendance summary
     */
    public function monthly(Request $request)
    {
        $month = $request->has('month') ? Carbon::parse($request->month) : Carbon::now();
        $branchId = $request->get('branch_id');

        $query = Employee::with('branch')
            ->select('employees.*')
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });

        $employees = $query->get();

        // Get attendance summary for each employee
        $employeeSummary = [];

        foreach ($employees as $employee) {
            $records = AttendanceRecord::where('employee_id', $employee->id)
                ->whereMonth('attendance_date', $month->month)
                ->whereYear('attendance_date', $month->year)
                ->get();

            $employeeSummary[] = [
                'employee' => $employee,
                'total_days' => $records->count(),
                'present_days' => $records->whereIn('status', ['present', 'late', 'half_day'])->count(),
                'absent_days' => $records->where('status', 'absent')->count(),
                'late_days' => $records->where('status', 'late')->count(),
                'total_hours' => round($records->sum('total_working_minutes') / 60, 2),
                'overtime_hours' => round($records->sum('overtime_minutes') / 60, 2),
            ];
        }

        $branches = Branch::where('status', 'active')->get();

        return view('attendance.reports.monthly', compact('month', 'employeeSummary', 'branches'));
    }

    /**
     * Employee-specific report
     */
    public function employeeReport(Request $request, Employee $employee)
    {
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date', 'desc')
            ->get();

        // Statistics
        $stats = [
            'total_days' => $records->count(),
            'present_days' => $records->whereIn('status', ['present', 'late', 'half_day'])->count(),
            'absent_days' => $records->where('status', 'absent')->count(),
            'late_days' => $records->where('status', 'late')->count(),
            'total_working_hours' => round($records->sum('total_working_minutes') / 60, 2),
            'overtime_hours' => round($records->sum('overtime_minutes') / 60, 2),
            'avg_working_hours' => $records->count() > 0
                ? round(($records->sum('total_working_minutes') / 60) / $records->count(), 2)
                : 0,
        ];

        return view('attendance.reports.employee', compact('employee', 'records', 'stats', 'startDate', 'endDate'));
    }

    /**
     * Branch-wise summary report
     */
    public function branchReport(Request $request)
    {
        $date = $request->has('date') ? Carbon::parse($request->date) : Carbon::today();

        $branches = Branch::where('status', 'active')->get();
        $branchSummary = [];

        foreach ($branches as $branch) {
            $records = AttendanceRecord::where('branch_id', $branch->id)
                ->where('attendance_date', $date->toDateString())
                ->get();

            $branchSummary[] = [
                'branch' => $branch,
                'total_employees' => Employee::where('branch_id', $branch->id)->count(),
                'present' => $records->whereIn('status', ['present', 'late'])->count(),
                'late' => $records->where('status', 'late')->count(),
                'absent' => $records->where('status', 'absent')->count(),
            ];
        }

        return view('attendance.reports.branch', compact('date', 'branchSummary'));
    }

    /**
     * Late arrivals report
     */
    public function lateReport(Request $request)
    {
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        $branchId = $request->get('branch_id');

        $query = AttendanceRecord::with(['employee', 'branch'])
            ->where('status', 'late')
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $lateRecords = $query->orderBy('attendance_date', 'desc')->paginate(50);

        $branches = Branch::where('status', 'active')->get();

        return view('attendance.reports.late', compact('lateRecords', 'branches', 'startDate', 'endDate'));
    }

    /**
     * Overtime report
     */
    public function overtimeReport(Request $request)
    {
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        $branchId = $request->get('branch_id');

        $query = AttendanceRecord::with(['employee', 'branch'])
            ->where('overtime_minutes', '>', 0)
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $overtimeRecords = $query->orderBy('overtime_minutes', 'desc')->paginate(50);

        $branches = Branch::where('status', 'active')->get();

        return view('attendance.reports.overtime', compact('overtimeRecords', 'branches', 'startDate', 'endDate'));
    }
}
