@extends('layouts.app')

@section('title') Payroll Adjustments @endsection

@section('content')
<x-page-title title="Payroll Adjustments" subtitle="Standalone admin adjustments applied before payroll generation" />

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('attendance.payroll.adjustments.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1 small fw-semibold">Employee</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(request('employee_id')==$emp->id)>{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1 small fw-semibold">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="earning"   @selected(request('type')=='earning')>Earning</option>
                    <option value="award"     @selected(request('type')=='award')>Award</option>
                    <option value="deduction" @selected(request('type')=='deduction')>Deduction</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1 small fw-semibold">Month</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">All Months</option>
                    @for($m=1;$m<=12;$m++)
                        <option value="{{ $m }}" @selected(request('month')==$m)>{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1 small fw-semibold">Year</label>
                <input type="number" name="year" value="{{ request('year', date('Y')) }}" class="form-control form-control-sm" min="2020" max="2100">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="{{ route('attendance.payroll.adjustments.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                <a href="{{ route('attendance.payroll.adjustments.create') }}" class="btn btn-sm btn-success ms-auto">
                    <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</span> Add Adjustment
                </a>
            </div>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Table --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Adjustments ({{ $adjustments->total() }})</h6>
        <small class="text-muted">Standalone = not yet linked to a payroll | Linked = applied to a specific payroll</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Period</th>
                        <th>Type</th>
                        <th>Code / Title</th>
                        <th>Amount (PKR)</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adj)
                    <tr>
                        <td class="text-muted small">{{ $adj->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $adj->employee->name ?? '—' }}</div>
                            <div class="small text-muted">{{ $adj->employee->department ?? '' }}</div>
                        </td>
                        <td class="small">{{ \Carbon\Carbon::create($adj->year,$adj->month)->format('M Y') }}</td>
                        <td>
                            @if($adj->adjustment_type=='earning')
                                <span class="badge bg-success-subtle text-success border border-success">Earning</span>
                            @elseif($adj->adjustment_type=='award')
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning">Award</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger">Deduction</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $adj->title ?: str_replace('_',' ',$adj->code ?? '') }}</div>
                            <div class="small text-muted font-monospace">{{ $adj->code }}</div>
                        </td>
                        <td class="{{ $adj->adjustment_type=='deduction'?'text-danger':'text-success' }} fw-bold">
                            {{ $adj->adjustment_type=='deduction'?'−':'+' }} {{ number_format((float) $adj->amount,2) }}
                        </td>
                        <td>
                            @if($adj->payroll_id)
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary">Linked to Payroll #{{ $adj->payroll_id }}</span>
                            @else
                                <span class="badge bg-info-subtle text-info border border-info">Standalone</span>
                            @endif
                        </td>
                        <td class="small text-muted" style="max-width:180px">{{ Str::limit($adj->notes ?? $adj->reason ?? '—', 60) }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('attendance.payroll.adjustments.show',$adj->id) }}" class="btn btn-xs btn-outline-primary" title="View">
                                    <span class="material-icons-outlined" style="font-size:15px">visibility</span>
                                </a>
                                @if(!$adj->payroll_id)
                                <a href="{{ route('attendance.payroll.adjustments.edit',$adj->id) }}" class="btn btn-xs btn-outline-warning" title="Edit">
                                    <span class="material-icons-outlined" style="font-size:15px">edit</span>
                                </a>
                                <form action="{{ route('attendance.payroll.adjustments.destroy',$adj->id) }}" method="POST" onsubmit="return confirm('Delete this adjustment?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete">
                                        <span class="material-icons-outlined" style="font-size:15px">delete</span>
                                    </button>
                                </form>
                                @else
                                <span class="text-muted small">Locked</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No adjustments found for the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($adjustments->hasPages())
    <div class="card-footer d-flex justify-content-end">
        {{ $adjustments->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
