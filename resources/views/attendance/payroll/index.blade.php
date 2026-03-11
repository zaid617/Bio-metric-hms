@extends('layouts.app')

@section('title')
    Attendance Payroll
@endsection

@push('css')
    <style>
        .status-draft { background-color: #ffc107; color: black; padding: 4px 8px; border-radius: 4px; }
        .status-reviewed { background-color: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; }
        .status-approved { background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; }
        .status-paid { background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; }
    </style>
@endpush

@section('content')
    <x-page-title title="Attendance Payroll" subtitle="Salary Management" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="mb-0">Payroll Records</h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('attendance.payroll.generate') }}" class="btn btn-primary">Generate Payroll</a>
                            <a href="{{ route('attendance.payroll.employee-view', ['period_month' => request('period_month', now()->format('Y-m'))]) }}" class="btn btn-outline-primary">Employee Payroll View</a>
                        </div>
                    </div>

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
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-2">
                                <label class="form-label">Period Month</label>
                                <input type="month" name="period_month" class="form-control" value="{{ request('period_month') }}">
                            </div>

                            <div class="col-md-3 mb-2">
                                <label class="form-label">Employee</label>
                                <select name="employee_id" class="form-select">
                                    <option value="">All Employees</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ (string) request('employee_id') === (string) $employee->id ? 'selected' : '' }}>
                                            {{ $employee->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-2">
                                <label class="form-label">Per Page</label>
                                <select name="per_page" class="form-select">
                                    <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                    <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    Filter
                                </button>
                            </div>
                        </div>
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
                                    <th>ID</th>
                                    <th>Employee</th>
                                    <th>Branch</th>
                                    <th>Period</th>
                                    <th>Present Days</th>
                                    <th>Basic Salary</th>
                                    <th>Awards</th>
                                    <th>Deductions</th>
                                    <th>Final Salary</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payrolls as $payroll)
                                    <tr>
                                        <td>{{ $payroll->id }}</td>
                                        <td>
                                            <strong>{{ $payroll->employee->name ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">{{ $payroll->employee->designation ?? '' }}</small>
                                        </td>
                                        <td>{{ $payroll->branch->name ?? 'N/A' }}</td>
                                        <td>
                                            {{ $payroll->payroll_period_start->format('M d') }} -
                                            {{ $payroll->payroll_period_end->format('M d, Y') }}
                                        </td>
                                        <td>{{ $payroll->present_days }}/{{ $payroll->total_working_days }}</td>
                                        <td>PKR {{ number_format($payroll->basic_salary ?? $payroll->base_salary, 2) }}</td>
                                        <td>PKR {{ number_format($payroll->awards_total ?? $payroll->bonus, 2) }}</td>
                                        <td>PKR {{ number_format($payroll->deductions_total ?? $payroll->deductions, 2) }}</td>
                                        <td><strong>PKR {{ number_format($payroll->final_salary ?? $payroll->final_settlement, 2) }}</strong></td>
                                        <td>
                                            <span class="status-{{ $payroll->status }}">
                                                {{ ucfirst($payroll->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('attendance.payroll.show', $payroll) }}"
                                                   class="btn btn-sm btn-primary" title="View">
                                                    <i class="material-icons-outlined">visibility</i>
                                                </a>
                                                @if($payroll->status == 'draft')
                                                    <a href="{{ route('attendance.payroll.edit', $payroll) }}"
                                                       class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="material-icons-outlined">edit</i>
                                                    </a>
                                                @endif
                                                @if($payroll->status != 'paid')
                                                    <form action="{{ route('attendance.payroll.approve', $payroll) }}"
                                                          method="POST" style="display:inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                            <i class="material-icons-outlined">check_circle</i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No payroll records found. Generate payroll to get started.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Showing {{ $payrolls->firstItem() ?? 0 }} to {{ $payrolls->lastItem() ?? 0 }}
                            of {{ $payrolls->total() }} records
                        </small>
                        <div>
                            {{ $payrolls->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
