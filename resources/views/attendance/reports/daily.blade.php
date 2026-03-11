@extends('layouts.app')

@section('title')
    Daily Attendance Report
@endsection

@push('css')
    <style>
        .status-present { background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; }
        .status-absent { background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; }
        .status-late { background-color: #ffc107; color: black; padding: 4px 8px; border-radius: 4px; }
        .status-half-day { background-color: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; }
        .summary-card { border-left: 4px solid; padding: 15px; margin-bottom: 15px; }
        .summary-present { border-color: #28a745; }
        .summary-absent { border-color: #dc3545; }
        .summary-late { border-color: #ffc107; }
    </style>
@endpush

@section('content')
    <x-page-title title="Daily Attendance Report" subtitle="Date-wise Attendance Summary" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <!-- Filter Form -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Select Date *</label>
                            <input type="date" name="date" class="form-control"
                                   value="{{ request('date', date('Y-m-d')) }}" required>
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

                        <div class="col-md-3">
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
                                     Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            @if(isset($summary))
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card summary-card summary-present">
                        <h3 class="mb-0">{{ $summary['present'] }}</h3>
                        <p class="mb-0 text-muted">Present</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card summary-card summary-absent">
                        <h3 class="mb-0">{{ $summary['absent'] }}</h3>
                        <p class="mb-0 text-muted">Absent</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card summary-card summary-late">
                        <h3 class="mb-0">{{ $summary['late'] }}</h3>
                        <p class="mb-0 text-muted">Late Arrivals</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card summary-card">
                        <h3 class="mb-0">{{ $summary['total'] }}</h3>
                        <p class="mb-0 text-muted">Total Employees</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Attendance Records -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Attendance Records - {{ request('date', date('Y-m-d')) }}</h5>
                    <div>
                        <button class="btn btn-sm btn-success" onclick="exportToExcel()">
                             Export Excel
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="exportToPDF()">
                             Export PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="attendanceTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Branch</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Working Hours</th>
                                    <th>Overtime</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $record)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $record->employee->name ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">{{ $record->employee->designation ?? '' }}</small>
                                        </td>
                                        <td>{{ $record->branch->name ?? 'N/A' }}</td>
                                        <td>{{ $record->check_in_time ? $record->check_in_time->format('h:i A') : '-' }}</td>
                                        <td>{{ $record->check_out_time ? $record->check_out_time->format('h:i A') : '-' }}</td>
                                        <td>{{ number_format($record->working_hours ?? 0, 2) }} hrs</td>
                                        <td>{{ number_format($record->overtime_hours ?? 0, 2) }} hrs</td>
                                        <td>
                                            <span class="status-{{ $record->status }}">
                                                {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No attendance records found for this date.</td>
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
        </div>
    </div>
@endsection

@push('script')
    <script>
        function exportToExcel() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.location.href = '{{ route("attendance.reports.daily") }}?' + params.toString();
        }

        function exportToPDF() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'pdf');
            window.location.href = '{{ route("attendance.reports.daily") }}?' + params.toString();
        }
    </script>
@endpush
