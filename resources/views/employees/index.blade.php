@extends('layouts.app')

@section('title', 'Employee Datatable')

@push('css')
<link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<style>
    td.text-nowrap {
        white-space: nowrap !important;
    }

    th.text-nowrap {
        white-space: nowrap !important;
    }

    .table-responsive::-webkit-scrollbar {
        display: none;
    }
    .table-responsive {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
@endpush

@section('content')
<x-page-title title="Employees" subtitle="Employee Data Table" />

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0 text-uppercase fw-bold">Employee List</h6>
    <a href="{{ url('/employees/create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Add New Employee
    </a>
</div>

<!-- Filters Card -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-title text-uppercase fw-bold mb-3">
            <i class="bi bi-funnel me-2"></i>Filters
        </h6>
        <form action="{{ route('employees.index') }}" method="GET" id="filterForm" class="row g-3">
            <!-- Branch Filter -->
            <div class="col-md-3">
                <label for="branchFilter" class="form-label fw-semibold">Branch</label>
                <select id="branchFilter" name="branch" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit();">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Department Filter -->
            <div class="col-md-3">
                <label for="departmentFilter" class="form-label fw-semibold">Department</label>
                <select id="departmentFilter" name="department" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit();">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department }}" {{ request('department') == $department ? 'selected' : '' }}>
                            {{ $department }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Designation Filter -->
            <div class="col-md-3">
                <label for="designationFilter" class="form-label fw-semibold">Designation</label>
                <select id="designationFilter" name="designation" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit();">
                    <option value="">All Designations</option>
                    @foreach ($designations as $designation)
                        <option value="{{ $designation }}" {{ request('designation') == $designation ? 'selected' : '' }}>
                            {{ $designation }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Shift Filter -->
            <div class="col-md-3">
                <label for="shiftFilter" class="form-label fw-semibold">Shift</label>
                <select id="shiftFilter" name="shift" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit();">
                    <option value="">All Shifts</option>
                    @foreach ($shifts as $shift)
                        <option value="{{ $shift }}" {{ request('shift') == $shift ? 'selected' : '' }}>
                            {{ $shift }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Clear Filters Button -->
            @if(request()->filled('branch') || request()->filled('department') || request()->filled('designation') || request()->filled('shift'))
                <div class="col-12 d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.href='{{ route('employees.index') }}';">
                        <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                    </button>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="example" class="table table-hover table-striped table-bordered align-middle text-nowrap" style="width:100%">
                <thead class="table-dark">
                    <tr class="text-center">
                        <th style="min-width: 40px;">#</th>
                        <th style="min-width: 150px;">Name</th>
                        <th style="min-width: 120px;">Designation</th>
                        <th style="min-width: 120px;">Branch</th>
                        <th style="min-width: 150px;">Department</th>
                        <th style="min-width: 100px;">Shift</th>
                        <th style="min-width: 120px;" class="text-end">Basic Salary</th>
                        <th style="min-width: 180px;" class="text-end">Salary Components</th>
                        <th style="min-width: 120px;">Phone</th>
                        <th style="min-width: 120px;">Joining Date</th>
                        <th class="text-nowrap" style="width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $index => $employee)
                    <tr>
                        <td class="text-center fw-semibold">{{ $index + 1 }}</td>

                        {{-- ✅ Prefix + Name --}}
                        <td class="fw-semibold">
                            {{ $employee->prefix }} {{ $employee->name }}
                        </td>

                        <td class="text-center">
                            <span class="badge bg-info text-dark px-2 py-1 fs-6">
                                {{ $employee->designation }}
                            </span>
                        </td>

                        <td class="text-center">
                            <span class="badge bg-light text-dark px-2 py-1 fs-6">
                                {{ $employee->branch_name }}
                            </span>
                        </td>

                        <td class="text-center">
                            <span class="badge bg-secondary text-white px-2 py-1 fs-6">
                                {{ $employee->department ?? 'N/A' }}
                            </span>
                        </td>

                        <td class="text-center">
                            @php
                                $shiftColors = [
                                    'Morning' => 'bg-success',
                                    'Afternoon' => 'bg-warning text-dark',
                                    'Evening' => 'bg-secondary'
                                ];
                            @endphp
                            <span class="badge {{ $shiftColors[$employee->shift] ?? 'bg-light text-dark' }} px-2 py-1 fs-6">
                                {{ $employee->shift ?? 'N/A' }}
                            </span>
                        </td>

                        <td class="text-end fw-bold">
                            {{ number_format($employee->basic_salary) }}
                        </td>

                        @php
                            $totalAllowances = (float)($employee->allowance_allied_health_council ?? 0)
                                + (float)($employee->allowance_house_job ?? 0)
                                + (float)($employee->allowance_conveyance ?? 0)
                                + (float)($employee->allowance_medical ?? 0)
                                + (float)($employee->allowance_house_rent ?? 0);
                            $otherAllowance = (float)($employee->other_allowance ?? 0);
                        @endphp
                        <td class="text-end">
                            <div class="small text-muted">Allowances: {{ number_format($totalAllowances, 2) }}</div>
                            <div class="small text-muted">Other: {{ number_format($otherAllowance, 2) }}</div>
                        </td>

                        <td class="text-center">
                            {{ $employee->phone }}
                        </td>

                        <td class="text-center">
                            {{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }}
                        </td>

                        {{-- Actions --}}
                        <td class="text-center text-nowrap">
                            <a href="{{ url('/employees/'.$employee->id.'/edit') }}" class="btn btn-sm btn-info text-white me-1">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <form action="{{ url('/employees/'.$employee->id) }}" method="POST"
                                  class="d-inline-block"
                                  onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/main.js') }}"></script>

<script>
$(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#example')) {
        $('#example').DataTable().clear().destroy();
    }

    $('#example').DataTable({
        pageLength: 10,
        ordering: true,
        lengthChange: false,
        responsive: true,
        autoWidth: false,
        scrollX: true,
        columnDefs: [
            { orderable: false, targets: -1, responsivePriority: 1 },
            { responsivePriority: 2, targets: 1 },
            { responsivePriority: 3, targets: 4 },
            { responsivePriority: 4, targets: 3 },
            { responsivePriority: 5, targets: 2 }
        ]
    });
});
</script>
@endpush
