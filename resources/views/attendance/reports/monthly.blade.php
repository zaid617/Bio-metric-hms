@extends('layouts.app')

@section('title')
    Monthly Attendance Report
@endsection

@push('css')
    <style>
        .attendance-cell { text-align: center; padding: 8px; font-size: 12px; }
        .cell-present { background-color: #d4edda; color: #155724; }
        .cell-absent { background-color: #f8d7da; color: #721c24; }
        .cell-late { background-color: #fff3cd; color: #856404; }
        .cell-half-day { background-color: #d1ecf1; color: #0c5460; }
        .cell-holiday { background-color: #e2e3e5; color: #383d41; }
        .summary-stats { font-size: 0.9rem; }
    </style>
@endpush

@section('content')
    <x-page-title title="Monthly Attendance Report" subtitle="Month-wise Attendance Summary" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <!-- Filter Form -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Select Month *</label>
                            <input type="month" name="month" class="form-control"
                                   value="{{ request('month', date('Y-m')) }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                 Filter
                            </button>
                        </div>

                        <div class="col-md-4 text-end">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                     Excel
                                </button>
                                <button type="button" class="btn btn-danger" onclick="exportToPDF()">
                                    PDF
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Monthly Summary -->
            @if(isset($monthlyStats))
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Monthly Summary - {{ request('month', date('Y-m')) }}</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <h4 class="text-success">{{ $monthlyStats['total_present'] }}</h4>
                            <p class="mb-0 text-muted">Total Present</p>
                        </div>
                        <div class="col-md-2">
                            <h4 class="text-danger">{{ $monthlyStats['total_absent'] }}</h4>
                            <p class="mb-0 text-muted">Total Absent</p>
                        </div>
                        <div class="col-md-2">
                            <h4 class="text-warning">{{ $monthlyStats['total_late'] }}</h4>
                            <p class="mb-0 text-muted">Late Arrivals</p>
                        </div>
                        <div class="col-md-2">
                            <h4 class="text-info">{{ number_format($monthlyStats['avg_attendance'], 1) }}%</h4>
                            <p class="mb-0 text-muted">Avg Attendance</p>
                        </div>
                        <div class="col-md-2">
                            <h4>{{ number_format($monthlyStats['total_overtime'], 1) }}</h4>
                            <p class="mb-0 text-muted">Overtime Hours</p>
                        </div>
                        <div class="col-md-2">
                            <h4>{{ $monthlyStats['working_days'] }}</h4>
                            <p class="mb-0 text-muted">Working Days</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Attendance Grid -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Employee-wise Attendance</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" style="font-size: 0.85rem;">
                            <thead class="table-dark">
                                <tr>
                                    <th style="min-width: 150px;">Employee</th>
                                    <th class="text-center">Present</th>
                                    <th class="text-center">Absent</th>
                                    <th class="text-center">Late</th>
                                    <th class="text-center">Total Hours</th>
                                    <th class="text-center">Overtime</th>
                                    <th class="text-center">Attendance %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employeeStats as $stat)
                                    <tr>
                                        <td>
                                            <strong>{{ $stat['employee_name'] }}</strong><br>
                                            <small class="text-muted">{{ $stat['branch_name'] }}</small>
                                        </td>
                                        <td class="text-center text-success">{{ $stat['present_days'] }}</td>
                                        <td class="text-center text-danger">{{ $stat['absent_days'] }}</td>
                                        <td class="text-center text-warning">{{ $stat['late_days'] }}</td>
                                        <td class="text-center">{{ number_format($stat['total_hours'], 1) }}</td>
                                        <td class="text-center">{{ number_format($stat['overtime_hours'], 1) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $stat['attendance_percentage'] >= 90 ? 'success' : ($stat['attendance_percentage'] >= 75 ? 'warning' : 'danger') }}">
                                                {{ number_format($stat['attendance_percentage'], 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No attendance data found for this month.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(isset($employeeStats))
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Legend:</strong>
                                <span class="badge bg-success">≥ 90%</span> Excellent |
                                <span class="badge bg-warning">75-89%</span> Average |
                                <span class="badge bg-danger">< 75%</span> Poor
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        function exportToExcel() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.location.href = '{{ route("attendance.reports.monthly") }}?' + params.toString();
        }

        function exportToPDF() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'pdf');
            window.location.href = '{{ route("attendance.reports.monthly") }}?' + params.toString();
        }
    </script>
@endpush
