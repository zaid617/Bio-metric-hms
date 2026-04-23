@extends('layouts.app')
@section('title')
    Checkup Details
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/input-tags/css/tagsinput.css') }}" rel="stylesheet">
@endpush

@section('content')
    <x-page-title title="Checkup" subtitle="Details" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                <div class="card-body">

                    <!-- Patient Name -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Patient:</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->patient_name ?? 'N/A' }}" readonly>
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Date:</strong></label>
                        <input type="text" class="form-control"
                               value="{{ \Carbon\Carbon::parse($checkup->created_at)->format('d-m-Y') }}" readonly>
                    </div>

                    <!-- Phone -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Phone:</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->patient_phone ?? 'N/A' }}" readonly>
                    </div>

                    <!-- Doctor -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Doctor:</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->doctor_name ?? 'N/A' }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Consultation Type:</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->consultation_type_display ?? ($checkup->consultation_type ?? 'Appointment') }}" readonly>
                    </div>

                    <!-- Branch -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Branch:</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->branch_name ?? 'N/A' }}" readonly>
                    </div>

                    <!-- Fee -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Consultation Fee (Rs):</strong></label>
                        <input type="text" class="form-control" value="{{ number_format((float) ($checkup->fee ?? 0), 2) }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Discount (%):</strong></label>
                        <input type="text" class="form-control" value="{{ number_format((float) ($checkup->discount ?? 0), 2) }}%" readonly>
                    </div>

                    <!-- Paid Amount -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Paid Amount (Rs):</strong></label>
                        <input type="text" class="form-control" value="{{ number_format((float) ($checkup->paid_amount ?? 0), 2) }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Pending Amount (Rs):</strong></label>
                        <input type="text" class="form-control" value="{{ number_format((float) ($checkup->pending_amount_resolved ?? $checkup->pending_amount ?? 0), 2) }}" readonly>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Payment Method:</strong></label>
                        <input type="text" class="form-control" value="{{ bank_get_name($checkup->payment_method) ?? 'N/A' }}" readonly>
                    </div>

                    <div class="card border mt-4">
                        <div class="card-header bg-light">
                            <strong>Update Paid Amount</strong>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('consultations.update-paid-amount', $checkup->id) }}" id="updatePaidAmountForm">
                                @csrf
                                @method('PATCH')

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="fee_preview" class="form-label">Total (Rs)</label>
                                        <input type="number" id="fee_preview" class="form-control" value="{{ (float) ($checkup->fee ?? 0) }}" step="0.01" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="discount_preview" class="form-label">Discount (%)</label>
                                        <input type="number" id="discount_preview" class="form-control" value="{{ (float) ($checkup->discount ?? 0) }}" step="0.01" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="pending_preview" class="form-label">Pending (Rs)</label>
                                        <input type="number" id="pending_preview" class="form-control" value="{{ (float) ($checkup->pending_amount_resolved ?? $checkup->pending_amount ?? 0) }}" step="0.01" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="paid_amount" class="form-label">New Paid Amount (Rs)</label>
                                        <input type="number" name="paid_amount" id="paid_amount" class="form-control" value="{{ old('paid_amount', $checkup->paid_amount ?? 0) }}" min="0" step="0.01" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="payment_method" class="form-label">Payment Method</label>
                                        <select name="payment_method" id="payment_method" class="form-select">
                                            <option value="">Keep Existing</option>
                                            <option value="0" {{ (string) old('payment_method') === '0' ? 'selected' : '' }}>Cash</option>
                                            @foreach(($banks ?? []) as $bank)
                                                <option value="{{ $bank->id }}" {{ (string) old('payment_method') === (string) $bank->id ? 'selected' : '' }}>
                                                    Bank {{ $bank->bank_name }} | ({{ $bank->account_no }}) | {{ $bank->account_title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">Update Paid Amount</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Checkup Status -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Payment Status:</strong></label>
                        @php
                            $pendingAmount = (float) ($checkup->pending_amount_resolved ?? $checkup->pending_amount ?? 0);
                            $paidAmount = (float) ($checkup->paid_amount ?? 0);
                        @endphp
                        @if($pendingAmount <= 0)
                            <span class="badge bg-success">Fully Paid</span>
                        @elseif($paidAmount > 0)
                            <span class="badge bg-warning text-dark">Partially Paid</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </div>

                    <a href="{{ route('consultations.index') }}" class="btn btn-secondary mt-3">Back to List</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const form = document.getElementById('updatePaidAmountForm');
            const paidInput = document.getElementById('paid_amount');
            const feeInput = document.getElementById('fee_preview');
            const discountInput = document.getElementById('discount_preview');
            const pendingInput = document.getElementById('pending_preview');

            if (!form || !paidInput || !feeInput || !discountInput || !pendingInput) {
                return;
            }

            const recalcPending = () => {
                const fee = parseFloat(feeInput.value) || 0;
                const discount = parseFloat(discountInput.value) || 0;
                const paid = parseFloat(paidInput.value) || 0;
                const discountAmount = fee * (discount / 100);
                const pending = Math.max(0, (fee - discountAmount) - paid);
                pendingInput.value = pending.toFixed(2);
            };

            paidInput.addEventListener('input', recalcPending);
            paidInput.addEventListener('change', recalcPending);

            form.addEventListener('submit', function (event) {
                const fee = parseFloat(feeInput.value) || 0;
                const discount = parseFloat(discountInput.value) || 0;
                const paid = parseFloat(paidInput.value) || 0;
                const discountAmount = fee * (discount / 100);
                const maxPayable = Math.max(0, fee - discountAmount);

                if (paid > maxPayable) {
                    event.preventDefault();
                    alert('Paid Amount cannot exceed Total after Discount.');
                }
            });

            recalcPending();
        })();
    </script>
    <!--plugins-->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
