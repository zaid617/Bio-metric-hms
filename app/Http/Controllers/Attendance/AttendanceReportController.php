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
        $user = auth()->user();
        $date = $request->has('date') ? Carbon::parse($request->date) : Carbon::today();
        $branchId = user_can_manage_all_branches($user)
            ? $request->get('branch_id')
            : user_branch_id($user);
        $status = $request->get('status');

        $query = AttendanceRecord::with(['employee', 'branch'])
            ->where('attendance_date', $date->toDateString());

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $statsQuery = clone $query;

        $records = $query
            ->orderBy('attendance_date', 'desc')
            ->orderBy('employee_id')
            ->paginate(50);

        // Statistics
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'present' => (clone $statsQuery)->whereIn('status', ['present', 'late'])->count(),
            'late' => (clone $statsQuery)->where('status', 'late')->count(),
            'absent' => (clone $statsQuery)->where('status', 'absent')->count(),
            'on_leave' => (clone $statsQuery)->where('status', 'leave')->count(),
        ];

        // Backward compatibility for views expecting $summary.
        $summary = $stats;

        $branches = user_can_manage_all_branches($user)
            ? Branch::where('status', 'active')->get()
            : Branch::where('status', 'active')->where('id', user_branch_id($user))->get();

        return view('attendance.reports.daily', compact('date', 'records', 'stats', 'summary', 'branches'));
    }

    /**
     * Monthly attendance summary
     */
    public function monthly(Request $request)
    {
        $user = auth()->user();
        $month = $request->has('month') ? Carbon::parse($request->month) : Carbon::now();
        $branchId = user_can_manage_all_branches($user)
            ? $request->get('branch_id')
            : user_branch_id($user);

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

        $branches = user_can_manage_all_branches($user)
            ? Branch::where('status', 'active')->get()
            : Branch::where('status', 'active')->where('id', user_branch_id($user))->get();

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

        $status = $request->get('status');

        $baseQuery = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($status) {
            $baseQuery->where('status', $status);
        }

        $recordsForStats = (clone $baseQuery)->get();
        $totalDays = $recordsForStats->count();
        $presentDays = $recordsForStats->whereIn('status', ['present', 'late', 'half_day'])->count();
        $totalHours = round($recordsForStats->sum('total_working_minutes') / 60, 2);
        $overtimeHours = round($recordsForStats->sum('overtime_minutes') / 60, 2);
        $averageHours = $totalDays > 0 ? round($totalHours / $totalDays, 2) : 0;
        $attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;

        $records = $baseQuery
            ->orderBy('attendance_date', 'desc')
            ->paginate(50);

        // Statistics
        $stats = [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $recordsForStats->where('status', 'absent')->count(),
            'late_days' => $recordsForStats->where('status', 'late')->count(),
            'total_working_hours' => $totalHours,
            'overtime_hours' => $overtimeHours,
            'avg_working_hours' => $averageHours,

            // Compatibility keys for current blade template.
            'total_hours' => $totalHours,
            'avg_hours_per_day' => $averageHours,
            'attendance_percentage' => $attendancePercentage,
        ];

        $employees = Employee::with('branch')
            ->select('id', 'name', 'designation', 'branch_id')
            ->orderBy('name')
            ->get();

        return view('attendance.reports.employee', compact('employee', 'records', 'stats', 'startDate', 'endDate', 'employees'));
    }

    /**
     * Branch-wise summary report
     */
    public function branchReport(Request $request)
    {
        $user = auth()->user();
        $date = $request->has('date') ? Carbon::parse($request->date) : Carbon::today();
        $branchId = user_can_manage_all_branches($user)
            ? $request->get('branch_id')
            : user_branch_id($user);

        $branch = Branch::where('status', 'active')
            ->when($branchId, fn ($query) => $query->where('id', $branchId))
            ->first();

        if (!$branch) {
            return back()->with('error', 'No branch found for this report.');
        }

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->start_date)
            : $date->copy()->startOfMonth();
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->end_date)
            : $date->copy()->endOfMonth();

        $records = AttendanceRecord::with(['employee', 'branch'])
            ->where('branch_id', $branch->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $dailyStats = $records
            ->groupBy(fn ($record) => optional($record->attendance_date)->toDateString() ?? (string) $record->attendance_date)
            ->map(function ($dayRecords, $dateKey) {
                $present = $dayRecords->whereIn('status', ['present', 'late'])->count();
                $absent = $dayRecords->where('status', 'absent')->count();
                $late = $dayRecords->where('status', 'late')->count();
                $total = max($dayRecords->count(), 1);

                return [
                    'date' => Carbon::parse($dateKey),
                    'present' => $present,
                    'absent' => $absent,
                    'late' => $late,
                    'attendance_percentage' => round(($present / $total) * 100, 1),
                    'working_hours' => round((float) $dayRecords->sum('working_hours'), 1),
                    'overtime_hours' => round((float) $dayRecords->sum('overtime_hours'), 1),
                ];
            })
            ->sortByDesc('date')
            ->values();

        $employeePerformance = $records
            ->groupBy('employee_id')
            ->map(function ($employeeRecords) {
                $employee = $employeeRecords->first()->employee;
                $present = $employeeRecords->whereIn('status', ['present', 'late'])->count();
                $absent = $employeeRecords->where('status', 'absent')->count();
                $late = $employeeRecords->where('status', 'late')->count();
                $total = max($employeeRecords->count(), 1);

                return [
                    'name' => $employee->name ?? 'N/A',
                    'designation' => $employee->designation ?? 'N/A',
                    'branch_name' => $employee->branch->name ?? 'N/A',
                    'present_days' => $present,
                    'absent_days' => $absent,
                    'late_days' => $late,
                    'total_hours' => round((float) $employeeRecords->sum('working_hours'), 1),
                    'overtime_hours' => round((float) $employeeRecords->sum('overtime_hours'), 1),
                    'attendance_percentage' => round(($present / $total) * 100, 1),
                ];
            })
            ->sortByDesc('attendance_percentage')
            ->values();

        $branchStats = [
            'total_employees' => Employee::where('branch_id', $branch->id)->count(),
            'total_present' => $records->whereIn('status', ['present', 'late'])->count(),
            'total_absent' => $records->where('status', 'absent')->count(),
            'total_late' => $records->where('status', 'late')->count(),
            'avg_attendance' => $records->count() > 0
                ? round(($records->whereIn('status', ['present', 'late'])->count() / $records->count()) * 100, 1)
                : 0,
            'total_overtime' => round((float) $records->sum('overtime_hours'), 1),
        ];

        return view('attendance.reports.branch', compact('branch', 'branchStats', 'dailyStats', 'employeePerformance', 'startDate', 'endDate'));
    }

    /**
     * Late arrivals report
     */
    public function lateReport(Request $request)
    {
        $user = auth()->user();
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        $branchId = user_can_manage_all_branches($user)
            ? $request->get('branch_id')
            : user_branch_id($user);

        $query = AttendanceRecord::with(['employee', 'branch'])
            ->where('status', 'late')
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $lateRecords = $query->orderBy('attendance_date', 'desc')->paginate(50);

        $branches = user_can_manage_all_branches($user)
            ? Branch::where('status', 'active')->get()
            : Branch::where('status', 'active')->where('id', user_branch_id($user))->get();

        return view('attendance.reports.late', compact('lateRecords', 'branches', 'startDate', 'endDate'));
    }

    /**
     * Overtime report
     */
    public function overtimeReport(Request $request)
    {
        $user = auth()->user();
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        $branchId = user_can_manage_all_branches($user)
            ? $request->get('branch_id')
            : user_branch_id($user);

        $query = AttendanceRecord::with(['employee', 'branch'])
            ->where('overtime_minutes', '>', 0)
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $overtimeRecords = $query->orderBy('overtime_minutes', 'desc')->paginate(50);

        $branches = user_can_manage_all_branches($user)
            ? Branch::where('status', 'active')->get()
            : Branch::where('status', 'active')->where('id', user_branch_id($user))->get();

        return view('attendance.reports.overtime', compact('overtimeRecords', 'branches', 'startDate', 'endDate'));
    }
}
