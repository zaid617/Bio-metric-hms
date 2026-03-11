@extends('layouts.app')

@section('title')
    Employee Attendance Report
@endsection

@push('css')
    <style>
        .status-present { background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; }
        .status-absent { background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; }
        .status-late { background-color: #ffc107; color: black; padding: 4px 8px; border-radius: 4px; }
        .status-half-day { background-color: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; }
        .employee-summary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; }
        .stat-box { background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px; margin-bottom: 10px; }
    </style>
@endpush

@section('content')
    <x-page-title title="Employee Attendance Report" subtitle="Individual Employee Attendance History" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <!-- Filter Form -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Select Employee *</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">Choose Employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->name }} - {{ $employee->designation }}
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

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                                <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="half_day" {{ request('status') == 'half_day' ? 'selected' : '' }}>Half Day</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                     Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if(isset($employee))
            <!-- Employee Summary Card -->
            <div class="card mb-3">
                <div class="card-body employee-summary">
                    <div class="row">
                        <div class="col-md-4">
                            <h4 class="mb-3">{{ $employee->name }}</h4>
                            <p class="mb-1"><strong>Employee ID:</strong> {{ $employee->id }}</p>
                            <p class="mb-1"><strong>Designation:</strong> {{ $employee->designation }}</p>
                            <p class="mb-1"><strong>Branch:</strong> {{ $employee->branch->name ?? 'N/A' }}</p>
                            <p class="mb-1"><strong>Period:</strong> {{ request('start_date') }} to {{ request('end_date') }}</p>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <h3 class="mb-0">{{ $stats['present_days'] }}</h3>
                                        <small>Present Days</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <h3 class="mb-0">{{ $stats['absent_days'] }}</h3>
                                        <small>Absent Days</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <h3 class="mb-0">{{ $stats['late_days'] }}</h3>
                                        <small>Late Arrivals</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <h3 class="mb-0">{{ number_format($stats['attendance_percentage'], 1) }}%</h3>
                                        <small>Attendance</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <div class="stat-box text-center">
                                        <h4 class="mb-0">{{ number_format($stats['total_hours'], 1) }}</h4>
                                        <small>Total Hours</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box text-center">
                                        <h4 class="mb-0">{{ number_format($stats['overtime_hours'], 1) }}</h4>
                                        <small>Overtime Hours</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box text-center">
                                        <h4 class="mb-0">{{ number_format($stats['avg_hours_per_day'], 1) }}</h4>
                                        <small>Avg Hours/Day</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Records -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Attendance History</h6>
                    <div>
                        <button class="btn btn-sm btn-success" onclick="exportToExcel()">
                             Excel
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="exportToPDF()">
                             PDF
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="window.print()">
                             Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Working Hours</th>
                                    <th>Overtime</th>
                                    <th>Late By</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $record)
                                    <tr>
                                        <td>{{ $record->date->format('M d, Y') }}</td>
                                        <td>{{ $record->date->format('l') }}</td>
                                        <td>{{ $record->check_in_time ? $record->check_in_time->format('h:i A') : '-' }}</td>
                                        <td>{{ $record->check_out_time ? $record->check_out_time->format('h:i A') : '-' }}</td>
                                        <td>{{ number_format($record->working_hours ?? 0, 2) }} hrs</td>
                                        <td>{{ number_format($record->overtime_hours ?? 0, 2) }} hrs</td>
                                        <td>{{ $record->late_by_minutes ? $record->late_by_minutes . ' min' : '-' }}</td>
                                        <td>
                                            <span class="status-{{ $record->status }}">
                                                {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                            </span>
                                        </td>
                                        <td>{{ $record->remarks ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No attendance records found for selected period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $records->appends(request()->query())->links() }}
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
            window.location.href = '{{ route("attendance.reports.employee") }}?' + params.toString();
        }

        function exportToPDF() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'pdf');
            window.location.href = '{{ route("attendance.reports.employee") }}?' + params.toString();
        }
    </script>
@endpush
