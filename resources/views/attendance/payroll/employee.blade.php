@extends('layouts.app')

@section('title')
    Employee Payroll View
@endsection

@section('content')
    <x-page-title title="Employee Payroll View" subtitle="Monthly payroll snapshot" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="mb-0">Payroll For {{ \Carbon\Carbon::createFromFormat('Y-m', $periodMonth)->format('F Y') }}</h5>
                        <a href="{{ route('attendance.payroll.index', ['period_month' => $periodMonth]) }}" class="btn btn-secondary">Back</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Employee</th>
                                    <th>Branch</th>
                                    <th>Basic</th>
                                    <th>Earnings</th>
                                    <th>Awards</th>
                                    <th>Deductions</th>
                                    <th>Final Salary</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payrolls as $payroll)
                                    <tr>
                                        <td>{{ $payroll->employee->name ?? 'N/A' }}</td>
                                        <td>{{ $payroll->branch->name ?? 'N/A' }}</td>
                                        <td>PKR {{ number_format($payroll->basic_salary ?? $payroll->base_salary, 2) }}</td>
                                        <td>PKR {{ number_format(collect($payroll->earnings_breakdown ?? [])->sum('amount'), 2) }}</td>
                                        <td>PKR {{ number_format($payroll->awards_total ?? 0, 2) }}</td>
                                        <td>PKR {{ number_format($payroll->deductions_total ?? 0, 2) }}</td>
                                        <td><strong>PKR {{ number_format($payroll->final_salary ?? $payroll->final_settlement, 2) }}</strong></td>
                                        <td>
                                            <a href="{{ route('attendance.payroll.show', $payroll) }}" class="btn btn-sm btn-primary">Details</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No payroll records found for the selected month.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
