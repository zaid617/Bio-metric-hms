@extends('layouts.app')

@section('title')
    Payroll Management
@endsection

@push('css')
<style>
    .stat-card { border-left: 4px solid; border-radius: 6px; }
    .stat-card.blue   { border-left-color: #0d6efd; }
    .stat-card.yellow { border-left-color: #ffc107; }
    .stat-card.green  { border-left-color: #198754; }
    .stat-card.cyan   { border-left-color: #0dcaf0; }
    .stat-card.red    { border-left-color: #dc3545; }
    .stat-icon { width:46px;height:46px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:22px; }
    .payroll-table td { vertical-align: middle; }
    .badge-draft    { background-color:#ffc107;color:#000; }
    .badge-approved { background-color:#198754;color:#fff; }
    .badge-reviewed { background-color:#0dcaf0;color:#000; }
    .badge-paid     { background-color:#6c757d;color:#fff; }
</style>
@endpush

@section('content')
<x-page-title title="Payroll Management" subtitle="Salary Calculation & Processing" />

<div class="row">
    <div class="col-xl-12 mx-auto">

        {{-- â”€â”€ Action Buttons â”€â”€ --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('attendance.payroll.generate') }}" class="btn btn-primary">
                        <span class="material-icons-outlined me-1" style="font-size:18px;vertical-align:middle">add_circle</span>
                        Generate Payroll
                    </a>
                </div>
            </div>
        </div>

        {{-- â”€â”€ Payroll Records â”€â”€ --}}
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Payroll Records</h5>

                {{-- Filters --}}
                <form method="GET" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Branch</label>
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
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="draft"    {{ request('status') == 'draft'    ? 'selected' : '' }}>Draft</option>
                                <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="paid"     {{ request('status') == 'paid'     ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Period</label>
                            <input type="month" name="period_month" class="form-control"
                                   value="{{ request('period_month') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Employee</label>
                            <select name="employee_id" class="form-select">
                                <option value="">All Employees</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ (string) request('employee_id') === (string) $employee->id ? 'selected' : '' }}>
                                        {{ $employee->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Per Page</label>
                            <div class="d-flex gap-1">
                                <select name="per_page" class="form-select">
                                    @foreach([10, 15, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary px-3">
                                    <span class="material-icons-outlined" style="font-size:18px">filter_list</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table table-hover payroll-table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Employee</th>
                                <th>Branch</th>
                                <th>Period</th>
                                <th>Basic Salary</th>
                                <th>Incentives</th>
                                <th>Awards</th>
                                <th>Deductions</th>
                                <th class="text-end">Net Salary</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payrolls as $payroll)
                            <tr>
                                <td class="text-muted small">{{ $payroll->id }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $payroll->employee->name ?? 'â€”' }}</div>
                                    <div class="text-muted small">{{ $payroll->employee->designation ?? '' }}</div>
                                </td>
                                <td class="small">{{ $payroll->branch->name ?? 'â€”' }}</td>
                                <td class="small">
                                    {{ str_pad($payroll->month, 2, '0', STR_PAD_LEFT) }}/{{ $payroll->year }}
                                </td>
                                <td>PKR {{ number_format($payroll->basic_salary ?? $payroll->base_salary, 0) }}</td>
                                <td class="text-success">
                                    PKR {{ number_format(
                                        ($payroll->satisfactory_sessions ?? 0)
                                        + ($payroll->treatment_extension_commission ?? 0)
                                        + ($payroll->satisfaction_bonus ?? 0)
                                        + ($payroll->assessment_bonus ?? 0)
                                        + ($payroll->reference_bonus ?? 0)
                                        + ($payroll->personal_patient_commission ?? 0)
                                        + ($payroll->additional_salary ?? 0)
                                        + ($payroll->overtime ?? 0), 0) }}
                                </td>
                                <td class="text-success">PKR {{ number_format($payroll->awards_total ?? $payroll->bonus ?? 0, 0) }}</td>
                                <td class="text-danger">PKR {{ number_format($payroll->deductions_total ?? $payroll->deductions ?? 0, 0) }}</td>
                                <td class="text-end fw-bold text-success">
                                    PKR {{ number_format($payroll->final_salary ?? $payroll->final_settlement ?? 0, 0) }}
                                </td>
                                <td>
                                    <span class="badge badge-{{ $payroll->status ?? 'draft' }}">
                                        {{ ucfirst($payroll->status ?? 'draft') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('attendance.payroll.show', $payroll->id) }}"
                                           class="btn btn-sm btn-outline-primary" title="View">
                                            <span class="material-icons-outlined" style="font-size:16px">visibility</span>
                                        </a>
                                        @if($payroll->status == 'draft')
                                        <a href="{{ route('attendance.payroll.edit', $payroll->id) }}"
                                           class="btn btn-sm btn-outline-warning" title="Adjust">
                                            <span class="material-icons-outlined" style="font-size:16px">edit</span>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <span class="material-icons-outlined d-block mb-2" style="font-size:40px">inbox</span>
                                    No payroll records found for the selected filters.
                                    <br>
                                    <a href="{{ route('attendance.payroll.generate') }}" class="btn btn-primary btn-sm mt-3">
                                        Generate Payroll Now
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <p class="text-muted small mb-0">
                        Showing {{ $payrolls->firstItem() ?? 0 }} to {{ $payrolls->lastItem() ?? 0 }}
                        of {{ $payrolls->total() }} records
                    </p>
                    {{ $payrolls->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

