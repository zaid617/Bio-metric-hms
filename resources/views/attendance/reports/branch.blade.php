@extends('layouts.app')

@section('title')
    Branch Attendance Report
@endsection

@push('css')
    <style>
        .branch-card { border-left: 5px solid #007bff; margin-bottom: 20px; }
        .metric-box { text-align: center; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 15px; }
        .metric-value { font-size: 2rem; font-weight: bold; color: #007bff; }
        .metric-label { color: #6c757d; font-size: 0.9rem; }
        .comparison-chart { height: 300px; }
    </style>
@endpush

@section('content')
    <x-page-title title="Branch Attendance Report" subtitle="Branch-wise Attendance Statistics" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <!-- Filter Form -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Select Branch *</label>
                            <select name="branch_id" class="form-select" required>
                                <option value="">Choose Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Start Date *</label>
                            <input type="date" name="start_date" class="form-control"
                                   value="{{ request('start_date', date('Y-m-01')) }}" required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">End Date *</label>
                            <input type="date" name="end_date" class="form-control"
                                   value="{{ request('end_date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                     Generate Report
                                </button>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                     Export
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if(isset($branchStats))
            <!-- Branch Overview -->
            <div class="card branch-card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">{{ $branch->name }} - Overview</h5>
                    <small>Period: {{ request('start_date') }} to {{ request('end_date') }}</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value text-primary">{{ $branchStats['total_employees'] }}</div>
                                <div class="metric-label">Total Employees</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value text-success">{{ $branchStats['total_present'] }}</div>
                                <div class="metric-label">Total Present</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value text-danger">{{ $branchStats['total_absent'] }}</div>
                                <div class="metric-label">Total Absent</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value text-warning">{{ $branchStats['total_late'] }}</div>
                                <div class="metric-label">Late Arrivals</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value text-info">{{ number_format($branchStats['avg_attendance'], 1) }}%</div>
                                <div class="metric-label">Avg Attendance</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value">{{ number_format($branchStats['total_overtime'], 1) }}</div>
                                <div class="metric-label">Overtime Hrs</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Breakdown -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Daily Attendance Breakdown</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th class="text-center">Present</th>
                                    <th class="text-center">Absent</th>
                                    <th class="text-center">Late</th>
                                    <th class="text-center">Attendance %</th>
                                    <th class="text-center">Working Hours</th>
                                    <th class="text-center">Overtime</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dailyStats as $daily)
                                    <tr>
                                        <td>{{ $daily['date']->format('M d, Y') }}</td>
                                        <td>{{ $daily['date']->format('l') }}</td>
                                        <td class="text-center text-success"><strong>{{ $daily['present'] }}</strong></td>
                                        <td class="text-center text-danger"><strong>{{ $daily['absent'] }}</strong></td>
                                        <td class="text-center text-warning"><strong>{{ $daily['late'] }}</strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $daily['attendance_percentage'] >= 90 ? 'success' : ($daily['attendance_percentage'] >= 75 ? 'warning' : 'danger') }}">
                                                {{ number_format($daily['attendance_percentage'], 1) }}%
                                            </span>
                                        </td>
                                        <td class="text-center">{{ number_format($daily['working_hours'], 1) }} hrs</td>
                                        <td class="text-center">{{ number_format($daily['overtime_hours'], 1) }} hrs</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No data available for selected period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Employee Performance -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Employee Performance - {{ $branch->name }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Employee</th>
                                    <th>Designation</th>
                                    <th class="text-center">Present</th>
                                    <th class="text-center">Absent</th>
                                    <th class="text-center">Late</th>
                                    <th class="text-center">Total Hours</th>
                                    <th class="text-center">Overtime</th>
                                    <th class="text-center">Attendance %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employeePerformance as $emp)
                                    <tr>
                                        <td><strong>{{ $emp['name'] }}</strong></td>
                                        <td>{{ $emp['designation'] }}</td>
                                        <td class="text-center text-success">{{ $emp['present_days'] }}</td>
                                        <td class="text-center text-danger">{{ $emp['absent_days'] }}</td>
                                        <td class="text-center text-warning">{{ $emp['late_days'] }}</td>
                                        <td class="text-center">{{ number_format($emp['total_hours'], 1) }}</td>
                                        <td class="text-center">{{ number_format($emp['overtime_hours'], 1) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $emp['attendance_percentage'] >= 90 ? 'success' : ($emp['attendance_percentage'] >= 75 ? 'warning' : 'danger') }}">
                                                {{ number_format($emp['attendance_percentage'], 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No employee data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('script')
    <script>
        function exportToExcel() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.location.href = '{{ route("attendance.reports.branch") }}?' + params.toString();
        }
    </script>
@endpush
