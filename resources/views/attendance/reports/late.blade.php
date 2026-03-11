@extends('layouts.app')

@section('title')
    Late Arrivals Report
@endsection

@push('css')
    <style>
        .late-minor { background-color: #fff3cd; }
        .late-moderate { background-color: #ffecb5; }
        .late-severe { background-color: #f8d7da; }
        .severity-badge { padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; }
    </style>
@endpush

@section('content')
    <x-page-title title="Late Arrivals Report" subtitle="Track Late Coming Employees" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <!-- Filter Form -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
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
                            <label class="form-label">Min Late Minutes</label>
                            <input type="number" name="min_late_minutes" class="form-control"
                                   value="{{ request('min_late_minutes', 5) }}" min="1">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                 Filter
                            </button>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-success w-100" onclick="exportToExcel()">
                                 Export
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            @if(isset($summary))
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-danger">{{ $summary['total_late_instances'] }}</h3>
                            <p class="mb-0 text-muted">Total Late Instances</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-warning">{{ $summary['unique_employees'] }}</h3>
                            <p class="mb-0 text-muted">Employees</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info">{{ number_format($summary['avg_late_minutes'], 1) }}</h3>
                            <p class="mb-0 text-muted">Avg Late (minutes)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-danger">{{ $summary['max_late_minutes'] }}</h3>
                            <p class="mb-0 text-muted">Max Late (minutes)</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Late Records -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Late Arrival Records</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Branch</th>
                                    <th>Shift Start</th>
                                    <th>Actual Check In</th>
                                    <th>Late By</th>
                                    <th>Severity</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lateRecords as $record)
                                    <tr class="{{ $record->late_by_minutes > 30 ? 'late-severe' : ($record->late_by_minutes > 15 ? 'late-moderate' : 'late-minor') }}">
                                        <td>{{ $record->date->format('M d, Y') }}</td>
                                        <td>
                                            <strong>{{ $record->employee->name ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">{{ $record->employee->designation ?? '' }}</small>
                                        </td>
                                        <td>{{ $record->branch->name ?? 'N/A' }}</td>
                                        <td>{{ $record->shift_start_time ?? 'N/A' }}</td>
                                        <td><strong>{{ $record->check_in_time ? $record->check_in_time->format('h:i A') : 'N/A' }}</strong></td>
                                        <td>
                                            <span class="badge bg-danger">{{ $record->late_by_minutes }} min</span>
                                        </td>
                                        <td>
                                            @if($record->late_by_minutes > 30)
                                                <span class="severity-badge bg-danger text-white">Severe</span>
                                            @elseif($record->late_by_minutes > 15)
                                                <span class="severity-badge bg-warning">Moderate</span>
                                            @else
                                                <span class="severity-badge bg-info text-white">Minor</span>
                                            @endif
                                        </td>
                                        <td>{{ $record->remarks ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No late arrivals found for the selected period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $lateRecords->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>

            <!-- Employee-wise Late Summary -->
            @if(isset($employeeSummary))
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Employee-wise Late Summary</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Employee</th>
                                    <th>Branch</th>
                                    <th class="text-center">Total Late Days</th>
                                    <th class="text-center">Avg Late (min)</th>
                                    <th class="text-center">Max Late (min)</th>
                                    <th class="text-center">Minor (≤15 min)</th>
                                    <th class="text-center">Moderate (16-30 min)</th>
                                    <th class="text-center">Severe (>30 min)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employeeSummary as $emp)
                                    <tr>
                                        <td>
                                            <strong>{{ $emp['employee_name'] }}</strong><br>
                                            <small class="text-muted">{{ $emp['designation'] }}</small>
                                        </td>
                                        <td>{{ $emp['branch_name'] }}</td>
                                        <td class="text-center"><strong>{{ $emp['total_late_days'] }}</strong></td>
                                        <td class="text-center">{{ number_format($emp['avg_late_minutes'], 1) }}</td>
                                        <td class="text-center text-danger">{{ $emp['max_late_minutes'] }}</td>
                                        <td class="text-center">{{ $emp['minor_count'] }}</td>
                                        <td class="text-center">{{ $emp['moderate_count'] }}</td>
                                        <td class="text-center">{{ $emp['severe_count'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No data available.</td>
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
            window.location.href = '{{ route("attendance.reports.late") }}?' + params.toString();
        }
    </script>
@endpush
