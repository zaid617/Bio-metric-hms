@extends('layouts.app')
@section('title')
    Return Payment
@endsection

@section('content')
<x-page-title title="Return Payment" subtitle="Process Refund for Patient" />
<div class="row">

    <!-- Header / Patient & Invoice Info -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <h3 class="text-center mb-4 fw-bold">Return Payment</h3>
            <div class="row g-3">

                <!-- Patient Info -->
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light h-100">
                        <h6 class="text-primary fw-bold mb-2">Patient Information</h6>
                        <p class="mb-1"><strong>Name:</strong> {{ $session->patient->name }}</p>
                        <p class="mb-1"><strong>MR:</strong> {{ $session->patient->mr }}</p>
                        <p class="mb-1"><strong>Phone:</strong> {{ $session->patient->phone }}</p>
                        <p class="mb-1"><strong>Branch:</strong> {{ $session->patient->branch->name }}</p>
                    </div>
                </div>

                <!-- Invoice Info -->
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light h-100">
                        <h6 class="text-primary fw-bold mb-2">Invoice Details</h6>
                        <p class="mb-1"><strong>Invoice ID:</strong> #{{ $session->id }}</p>
                        <p class="mb-1"><strong>Date:</strong> {{ format_date($session->created_at)}}</p>
                        <p class="mb-0"><strong>Sessions:</strong>
                            @if($session->is_completed)
                                <span class="badge bg-success">Completed</span>
                            @else
                                <span class="badge bg-warning text-dark">Ongoing</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Billing Summary -->
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light h-100">
                        <h6 class="text-primary fw-bold mb-2">Billing Summary</h6>
                        <p class="mb-1"><strong>Total:</strong> {{ number_format($total_amount) }}</p>
                        <p class="mb-1 text-success"><strong>Paid:</strong> {{ number_format($paid_amount) }}</p>
                        <p class="mb-0 text-danger"><strong>Remaining:</strong> {{ number_format($remaining_amount) }}</p>
                        <p class="mb-0"><strong>Status:</strong>
                            @if($session->payment_status === 'paid')
                                <span class="badge bg-success">PAID</span>
                            @else
                                <span class="badge bg-warning text-dark">UNPAID</span>
                            @endif
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Payment Transactions</h5>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Payment Method</th>
                        <th>Remarks</th> <!-- NEW COLUMN -->
                        <th class="text-end">Amount</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $amount_sum = 0;
                    @endphp
                    @forelse($transactions as $key => $transaction)
                    @php
                        $amount_sum = $transaction->type === '+' ? $amount_sum + $transaction->amount : $amount_sum - $transaction->amount;
                    @endphp
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ format_datetime($transaction->created_at) }}</td>
                        <td>{{ bank_get_name($transaction->bank_id) }}</td>
                        <td>{{ $transaction->Remx ?? '-' }}</td> <!-- Show remarks -->
                        @if ($transaction->type === '+')
                        <td class="text-end">{{ number_format($transaction->amount, 2) }}</td>
                        @else
                        <td class="text-end text-danger">-{{ number_format($transaction->amount, 2) }}</td>
                        @endif
                        <td class="text-end">{{ number_format($amount_sum, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No transactions found</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">Total Amount:</th>
                        <th class="text-end">{{ number_format($total_amount) }}</th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-end">Paid:</th>
                        <th class="text-end text-success">{{ number_format($paid_amount) }}</th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-end">Remaining:</th>
                        <th class="text-end text-danger">{{ number_format($remaining_amount) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Refund Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Process Refund</h5>
            <form action="{{ route('payments.returnPayment') }}" method="POST" id="refundForm">
                @csrf
                <input type="hidden" name="session_id" value="{{ $session->id }}">
                <input type="hidden" name="remarks" id="refund_remarks_input"> <!-- NEW -->
                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="">Select Payment Method</option>
                            <option value="0">Cash</option>
                            @foreach ($banks as $bank)
                                <option value="{{ $bank->id }}">
                                    Bank {{ $bank->bank_name }} | ({{ $bank->account_no }}) | {{ $bank->account_title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Refund Amount</label>
                        <input type="number" step="1" name="amount" max="{{ $paid_amount }}" class="form-control" required id="refund_amount">
                    </div>

                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-danger w-100" id="refundBtn">Process Refund</button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- Print Button -->
    <div class="text-end">
        <button class="btn btn-success" onclick="window.print()">🖨 Print Ledger</button>
    </div>

</div>
@endsection

@push('script')
<script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/main.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const refundForm = document.getElementById('refundForm');
    const remarksInput = document.getElementById('refund_remarks_input');

    refundForm.addEventListener('submit', function(e) {
        e.preventDefault();

        let refundAmount = document.getElementById('refund_amount').value;
        if(!refundAmount || refundAmount <= 0){
            alert('Please enter a valid refund amount.');
            return;
        }

        // Ask for remarks
        let remarks = prompt('Enter remarks for this refund:');
        if(remarks === null) return; // user cancelled

        remarksInput.value = remarks; // set hidden input

        // Submit form
        refundForm.submit();
    });
});
</script>
@endpush
