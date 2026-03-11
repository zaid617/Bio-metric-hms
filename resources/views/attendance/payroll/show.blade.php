@extends('layouts.app')

@section('title')
    Payroll Details
@endsection

@push('css')
    <style>
        .payroll-summary-card { border-left: 4px solid #007bff; }
        .breakdown-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ddd; }
        .breakdown-label { font-weight: 500; }
        .breakdown-value { color: #28a745; }
        .breakdown-deduction { color: #dc3545; }
        .final-settlement { font-size: 1.5rem; font-weight: bold; color: #155724; background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 15px; }
    </style>
@endpush

@section('content')
    <x-page-title title="Payroll Details" subtitle="Employee #{{ $payroll->employee_id }}" />

    <div class="row">
        <div class="col-xl-10 mx-auto">
            <!-- Employee Info Card -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>{{ $payroll->employee->name ?? 'N/A' }}</h5>
                            <p class="mb-1"><strong>Designation:</strong> {{ $payroll->employee->designation ?? 'N/A' }}</p>
                            <p class="mb-1"><strong>Branch:</strong> {{ $payroll->branch->name ?? 'N/A' }}</p>
                            <p class="mb-1"><strong>Employee ID:</strong> {{ $payroll->employee_id }}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h6>Payroll Period</h6>
                            <p>{{ $payroll->payroll_period_start->format('F d, Y') }} - {{ $payroll->payroll_period_end->format('F d, Y') }}</p>
                            <span class="badge bg-primary">Status: {{ ucfirst($payroll->status) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Salary Breakdown Card -->
            <div class="card payroll-summary-card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Salary Breakdown ({{ str_pad($payroll->month, 2, '0', STR_PAD_LEFT) }}/{{ $payroll->year }})</h5>
                </div>
                <div class="card-body">
                    <div class="breakdown-row">
                        <span class="breakdown-label">Basic Salary</span>
                        <span class="breakdown-value">PKR {{ number_format($payroll->basic_salary ?? $payroll->base_salary, 2) }}</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Additional Salary</span>
                        <span class="breakdown-value">PKR {{ number_format($payroll->additional_salary ?? 0, 2) }}</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Total Working Days</span>
                        <span>{{ $payroll->total_working_days }} days</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Present Days</span>
                        <span class="breakdown-value">{{ $payroll->present_days }} days</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Absent Days</span>
                        <span class="breakdown-deduction">{{ $payroll->absent_days }} days</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Late Days</span>
                        <span class="breakdown-deduction">{{ $payroll->late_days }} days</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Total Working Hours</span>
                        <span>{{ number_format($payroll->total_working_hours, 2) }} hrs</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Overtime Hours</span>
                        <span class="breakdown-value">{{ number_format($payroll->overtime_hours, 2) }} hrs</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Overtime</span>
                        <span class="breakdown-value">PKR {{ number_format($payroll->overtime ?? $payroll->overtime_pay, 2) }}</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Satisfactory Sessions</span>
                        <span class="breakdown-value">PKR {{ number_format($payroll->satisfactory_sessions ?? 0, 2) }}</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Treatment Extension Commission</span>
                        <span class="breakdown-value">PKR {{ number_format($payroll->treatment_extension_commission ?? 0, 2) }}</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Satisfaction Bonus</span>
                        <span class="breakdown-value">PKR {{ number_format($payroll->satisfaction_bonus ?? 0, 2) }}</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Assessment Bonus</span>
                        <span class="breakdown-value">PKR {{ number_format($payroll->assessment_bonus ?? 0, 2) }}</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Reference Bonus</span>
                        <span class="breakdown-value">PKR {{ number_format($payroll->reference_bonus ?? 0, 2) }}</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Personal Patient Commission</span>
                        <span class="breakdown-value">PKR {{ number_format($payroll->personal_patient_commission ?? 0, 2) }}</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Awards Total</span>
                        <span class="breakdown-value">PKR {{ number_format($payroll->awards_total ?? 0, 2) }}</span>
                    </div>

                    <div class="breakdown-row">
                        <span class="breakdown-label">Total Deductions</span>
                        <span class="breakdown-deduction">- PKR {{ number_format($payroll->deductions_total ?? $payroll->deductions, 2) }}</span>
                    </div>

                    @if(($payroll->admin_adjustment_amount ?? 0) != 0)
                    <div class="breakdown-row">
                        <span class="breakdown-label">Admin Adjustment</span>
                        <span class="{{ $payroll->admin_adjustment_amount > 0 ? 'breakdown-value' : 'breakdown-deduction' }}">
                            {{ $payroll->admin_adjustment_amount > 0 ? '+' : '' }} PKR {{ number_format($payroll->admin_adjustment_amount, 2) }}
                        </span>
                    </div>
                    @endif

                    @if($payroll->admin_adjustment_note)
                    <div class="breakdown-row">
                        <span class="breakdown-label">Adjustment Reason</span>
                        <span class="text-muted">{{ $payroll->admin_adjustment_note }}</span>
                    </div>
                    @endif

                    @if(!empty($payroll->earnings_breakdown))
                        <hr>
                        <h6>Earnings</h6>
                        @foreach($payroll->earnings_breakdown as $item)
                            <div class="breakdown-row">
                                <span>{{ $item['type'] ?? 'EARNING' }}</span>
                                <span class="breakdown-value">PKR {{ number_format((float) ($item['amount'] ?? 0), 2) }}</span>
                            </div>
                        @endforeach
                    @endif

                    @if(!empty($payroll->awards_breakdown))
                        <hr>
                        <h6>Awards</h6>
                        @foreach($payroll->awards_breakdown as $item)
                            <div class="breakdown-row">
                                <span>{{ $item['type'] ?? 'AWARD' }}</span>
                                <span class="breakdown-value">PKR {{ number_format((float) ($item['amount'] ?? 0), 2) }}</span>
                            </div>
                        @endforeach
                    @endif

                    @if(!empty($payroll->deductions_breakdown))
                        <hr>
                        <h6>Deductions</h6>
                        @foreach($payroll->deductions_breakdown as $item)
                            <div class="breakdown-row">
                                <span>{{ $item['type'] ?? 'DEDUCTION' }}</span>
                                <span class="breakdown-deduction">PKR {{ number_format((float) ($item['amount'] ?? 0), 2) }}</span>
                            </div>
                        @endforeach
                    @endif

                    <div class="final-settlement">
                        <div class="d-flex justify-content-between">
                            <span>FINAL SETTLEMENT:</span>
                            <span>PKR {{ number_format($payroll->final_salary ?? $payroll->final_settlement, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('attendance.payroll.index') }}" class="btn btn-secondary">
                             Back to List
                        </a>

                        <div>
                            @if($payroll->status == 'draft')
                                <a href="{{ route('attendance.payroll.edit', $payroll) }}" class="btn btn-warning">
                                    Admin Adjustment
                                </a>
                                <form action="{{ route('attendance.payroll.approve', $payroll) }}"
                                      method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success"
                                            onclick="return confirm('Are you sure you want to approve this payroll?')">
                                         Approve
                                    </button>
                                </form>
                            @endif

                            @if($payroll->status == 'approved')
                                <form action="{{ route('attendance.payroll.mark-paid', $payroll) }}"
                                      method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary"
                                            onclick="return confirm('Mark this payroll as paid?')">
                                         Mark as Paid
                                    </button>
                                </form>
                            @endif

                            @if($payroll->status == 'paid')
                                <button class="btn btn-outline-primary" onclick="window.print()">
                                     Print Payslip
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
