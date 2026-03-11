@extends('layouts.app')

@section('title')
    Overtime Report
@endsection

@push('css')
    <style>
        .overtime-high { background-color: #d4edda; }
        .overtime-medium { background-color: #fff3cd; }
        .overtime-low { background-color: #f8f9fa; }
        .summary-metric { text-align: center; padding: 20px; border: 2px solid #dee2e6; border-radius: 10px; }
    </style>
@endpush

@section('content')
    <x-page-title title="Overtime Report" subtitle="Track Employee Overtime Hours" />

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
                            <label class="form-label">Min Overtime (hrs)</label>
                            <input type="number" name="min_overtime" class="form-control"
                                   value="{{ request('min_overtime', 0.5) }}" step="0.5" min="0">
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

            <!-- Summary Metrics -->
            @if(isset($summary))
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="summary-metric">
                        <h3 class="text-primary">{{ number_format($summary['total_overtime_hours'], 1) }}</h3>
                        <p class="mb-0 text-muted">Total Overtime Hours</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-metric">
                        <h3 class="text-success">₹{{ number_format($summary['total_overtime_pay'], 2) }}</h3>
                        <p class="mb-0 text-muted">Total Overtime Pay</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-metric">
                        <h3 class="text-info">{{ $summary['employees_with_overtime'] }}</h3>
                        <p class="mb-0 text-muted">Employees w/ Overtime</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-metric">
                        <h3 class="text-warning">{{ number_format($summary['avg_overtime_per_employee'], 1) }}</h3>
                        <p class="mb-0 text-muted">Avg Hours/Employee</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Overtime Records -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Overtime Records</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Branch</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Total Hours</th>
                                    <th>Standard Hours</th>
                                    <th>Overtime Hours</th>
                                    <th>Overtime Pay</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($overtimeRecords as $record)
                                    <tr class="{{ $record->overtime_hours >= 3 ? 'overtime-high' : ($record->overtime_hours >= 1.5 ? 'overtime-medium' : 'overtime-low') }}">
                                        <td>{{ $record->date->format('M d, Y') }}</td>
                                        <td>
                                            <strong>{{ $record->employee->name ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">{{ $record->employee->designation ?? '' }}</small>
                                        </td>
                                        <td>{{ $record->branch->name ?? 'N/A' }}</td>
                                        <td>{{ $record->check_in_time ? $record->check_in_time->format('h:i A') : 'N/A' }}</td>
                                        <td>{{ $record->check_out_time ? $record->check_out_time->format('h:i A') : 'N/A' }}</td>
                                        <td>{{ number_format($record->working_hours ?? 0, 2) }} hrs</td>
                                        <td>{{ $record->standard_hours ?? 8 }} hrs</td>
                                        <td>
                                            <span class="badge bg-success">{{ number_format($record->overtime_hours ?? 0, 2) }} hrs</span>
                                        </td>
                                        <td>
                                            <strong>₹{{ number_format($record->overtime_pay ?? 0, 2) }}</strong>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No overtime records found for the selected period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $overtimeRecords->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>

            <!-- Employee-wise Overtime Summary -->
            @if(isset($employeeSummary))
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Employee-wise Overtime Summary</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Rank</th>
                                    <th>Employee</th>
                                    <th>Branch</th>
                                    <th class="text-center">Days w/ Overtime</th>
                                    <th class="text-center">Total OT Hours</th>
                                    <th class="text-center">Avg OT/Day</th>
                                    <th class="text-center">Max OT (Single Day)</th>
                                    <th class="text-center">Total OT Pay</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employeeSummary as $index => $emp)
                                    <tr class="{{ $emp['total_overtime_hours'] >= 20 ? 'overtime-high' : ($emp['total_overtime_hours'] >= 10 ? 'overtime-medium' : '') }}">
                                        <td class="text-center"><strong>#{{ $index + 1 }}</strong></td>
                                        <td>
                                            <strong>{{ $emp['employee_name'] }}</strong><br>
                                            <small class="text-muted">{{ $emp['designation'] }}</small>
                                        </td>
                                        <td>{{ $emp['branch_name'] }}</td>
                                        <td class="text-center">{{ $emp['overtime_days'] }}</td>
                                        <td class="text-center"><strong>{{ number_format($emp['total_overtime_hours'], 1) }}</strong></td>
                                        <td class="text-center">{{ number_format($emp['avg_overtime_per_day'], 1) }}</td>
                                        <td class="text-center">{{ number_format($emp['max_overtime_single_day'], 1) }}</td>
                                        <td class="text-center"><strong class="text-success">₹{{ number_format($emp['total_overtime_pay'], 2) }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(isset($employeeSummary) && count($employeeSummary) > 0)
                            <tfoot class="table-secondary">
                                <tr>
                                    <th colspan="4" class="text-end">TOTAL:</th>
                                    <th class="text-center">{{ number_format(collect($employeeSummary)->sum('total_overtime_hours'), 1) }}</th>
                                    <th colspan="2"></th>
                                    <th class="text-center"><strong>₹{{ number_format(collect($employeeSummary)->sum('total_overtime_pay'), 2) }}</strong></th>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>Note:</strong> Overtime is calculated based on working hours exceeding the standard shift duration.
                            Overtime pay rate is {{ config('zkteco.payroll.overtime_rate_multiplier', 1.5) }}x of hourly rate.
                        </small>
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
            window.location.href = '{{ route("attendance.reports.overtime") }}?' + params.toString();
        }
    </script>
@endpush
