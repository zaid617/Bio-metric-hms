@extends('layouts.app')

@section('title')
    Admin Adjustment - Payroll
@endsection

@section('content')
    <x-page-title title="Admin Adjustment" subtitle="Modify Final Settlement" />

    <div class="row">
        <div class="col-xl-8 mx-auto">
            <!-- Current Payroll Summary -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">Current Payroll Calculation</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Employee:</strong> {{ $payroll->employee->name ?? 'N/A' }}</p>
                            <p><strong>Period:</strong> {{ $payroll->payroll_period_start->format('M d') }} - {{ $payroll->payroll_period_end->format('M d, Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Basic Salary:</strong> PKR {{ number_format($payroll->basic_salary ?? $payroll->base_salary, 2) }}</p>
                            <p><strong>Overtime:</strong> PKR {{ number_format($payroll->overtime ?? $payroll->overtime_pay, 2) }}</p>
                            <p><strong>Awards:</strong> PKR {{ number_format($payroll->awards_total ?? $payroll->bonus, 2) }}</p>
                            <p><strong>Deductions:</strong> PKR {{ number_format($payroll->deductions_total ?? $payroll->deductions, 2) }}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h5>Current Final Settlement:
                            <span class="text-success">PKR {{ number_format($payroll->final_salary ?? $payroll->final_settlement, 2) }}</span>
                        </h5>
                    </div>
                </div>
            </div>

            <!-- Adjustment Form -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h6 class="mb-0">Make Adjustments</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('attendance.payroll.update', $payroll) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="bonus" class="form-label">Bonus Amount (PKR)</label>
                            <input type="number" name="bonus" id="bonus" step="0.01"
                                   class="form-control @error('bonus') is-invalid @enderror"
                                   value="{{ old('bonus', $payroll->bonus) }}">
                            @error('bonus')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Additional bonus to be added</small>
                        </div>

                        <div class="mb-3">
                            <label for="deductions" class="form-label">Deductions (PKR)</label>
                            <input type="number" name="deductions" id="deductions" step="0.01"
                                   class="form-control @error('deductions') is-invalid @enderror"
                                   value="{{ old('deductions', $payroll->deductions) }}">
                            @error('deductions')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Total deductions (taxes, advances, etc.)</small>
                        </div>

                        <div class="mb-3">
                            <label for="admin_adjustment" class="form-label">Admin Adjustment (PKR)</label>
                            <input type="number" name="admin_adjustment_amount" id="admin_adjustment_amount" step="0.01"
                                   class="form-control @error('admin_adjustment_amount') is-invalid @enderror"
                                   value="{{ old('admin_adjustment_amount', $payroll->admin_adjustment_amount) }}">
                            @error('admin_adjustment_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Use positive value to add, negative to deduct</small>
                        </div>

                        <div class="mb-3">
                            <label for="admin_adjustment_note" class="form-label">Adjustment Reason</label>
                            <textarea name="admin_adjustment_note" id="admin_adjustment_note" rows="3"
                                      class="form-control @error('admin_adjustment_note') is-invalid @enderror"
                                      placeholder="Explain why this adjustment was made...">{{ old('admin_adjustment_note', $payroll->admin_adjustment_note) }}</textarea>
                            @error('admin_adjustment_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>
                        <h6>Manual Earning</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <input type="text" name="earning_code" class="form-control" placeholder="Code (e.g. ADDITIONAL_SALARY)">
                            </div>
                            <div class="col-md-4 mb-3">
                                <input type="number" step="0.01" min="0" name="earning_amount" class="form-control" placeholder="Amount">
                            </div>
                            <div class="col-md-4 mb-3">
                                <input type="text" name="earning_notes" class="form-control" placeholder="Notes">
                            </div>
                        </div>

                        <h6>Manual Deduction</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <input type="text" name="deduction_code" class="form-control" placeholder="Code (e.g. ADVANCE_SALARY_DEDUCTION)">
                            </div>
                            <div class="col-md-4 mb-3">
                                <input type="number" step="0.01" min="0" name="deduction_amount" class="form-control" placeholder="Amount">
                            </div>
                            <div class="col-md-4 mb-3">
                                <input type="text" name="deduction_notes" class="form-control" placeholder="Notes">
                            </div>
                        </div>

                        <h6>Manual Award</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <input type="text" name="award_code" class="form-control" placeholder="Code (e.g. PUNCTUALITY_AWARD)">
                            </div>
                            <div class="col-md-4 mb-3">
                                <input type="number" step="0.01" min="0" name="award_amount" class="form-control" placeholder="Amount">
                            </div>
                            <div class="col-md-4 mb-3">
                                <input type="text" name="award_notes" class="form-control" placeholder="Notes">
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="material-icons-outlined">warning</i>
                            <strong>Important:</strong> All adjustments will be reflected in the final settlement.
                            Make sure to provide a clear reason for any admin adjustments.
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('attendance.payroll.show', $payroll) }}" class="btn btn-secondary">
                                 Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                 Save Adjustments
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        // Calculate preview of final settlement
        function updatePreview() {
            const baseSalary = {{ $payroll->basic_salary ?? $payroll->base_salary }};
            const overtimePay = {{ $payroll->overtime ?? $payroll->overtime_pay }};
            const bonus = parseFloat(document.getElementById('bonus').value) || 0;
            const deductions = parseFloat(document.getElementById('deductions').value) || 0;
            const adminAdjustment = parseFloat(document.getElementById('admin_adjustment_amount').value) || 0;

            const finalSettlement = baseSalary + overtimePay + bonus - deductions + adminAdjustment;

            console.log('Calculated Final Settlement:', finalSettlement);
        }

        // Add event listeners to inputs
        ['bonus', 'deductions', 'admin_adjustment_amount'].forEach(id => {
            document.getElementById(id).addEventListener('input', updatePreview);
        });
    </script>
@endpush
