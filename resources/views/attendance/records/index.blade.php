@extends('layouts.app')

@section('title')
    Attendance Records
@endsection

@push('css')
    <style>
        .status-present { background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; }
        .status-late { background-color: #ffc107; color: black; padding: 4px 8px; border-radius: 4px; }
        .status-absent { background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; }
        .status-half_day { background-color: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; }
        .status-leave { background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; }
    </style>
@endpush

@section('content')
    <x-page-title title="Attendance Records" subtitle="Employee Attendance" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3 mb-2">
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

                            <div class="col-md-3 mb-2">
                                <label class="form-label">Employee</label>
                                <select name="employee_id" class="form-select">
                                    <option value="">All Employees</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 mb-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                                    <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                                    <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                                    <option value="half_day" {{ request('status') == 'half_day' ? 'selected' : '' }}>Half Day</option>
                                    <option value="leave" {{ request('status') == 'leave' ? 'selected' : '' }}>Leave</option>
                                </select>
                            </div>

                            <div class="col-md-2 mb-2">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>

                            <div class="col-md-2 mb-2">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            Filter
                        </button>
                        <a href="{{ route('attendance.records.index') }}" class="btn btn-secondary">
                             Reset
                        </a>
                    </form>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Branch</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Working Hours</th>
                                    <th>Overtime (mins)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $record)
                                    <tr>
                                        <td>{{ $record->attendance_date->format('Y-m-d') }}</td>
                                        <td>{{ $record->employee->name ?? 'N/A' }}</td>
                                        <td>{{ $record->branch->name ?? 'N/A' }}</td>
                                        <td>{{ $record->check_in ?? '-' }}</td>
                                        <td>
                                            {{ $record->check_out ?? '-' }}
                                            @if($record->is_checkout_missing)
                                                <span class="badge bg-warning">Missing</span>
                                            @endif
                                        </td>
                                        <td>{{ $record->working_hours ?? 0 }}h</td>
                                        <td>{{ $record->calculated_overtime_minutes ?? 0 }}</td>
                                        <td>
                                            <span class="status-{{ $record->status }}">
                                                {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('attendance.records.edit', $record) }}"
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="material-icons-outlined">edit</i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No attendance records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $records->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
