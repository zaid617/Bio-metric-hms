@extends('layouts.app')
@section('title')
    Invoice Ledger
@endsection

@section('content')
<x-page-title title="Invoice Ledger" subtitle="View Invoice Details" />
<div class="row">
    {{-- <pre>{{ print_r($session) }} </pre> --}}
    <!-- Header / Invoice Info -->
    <!-- Header / Invoice Info -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <h3 class="text-center mb-4 fw-bold">Invoice Ledger</h3>
            <div class="row g-3">
                <!-- Client Info -->
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light h-100">
                        <h6 class="text-primary fw-bold mb-2">Patient Information</h6>
                        <p class="mb-1"><strong>Name:</strong> {{ $session->patient->name }}</p>
                        <p class="mb-1"><strong>MR:</strong> {{ $session->patient->mr }}</p>
                        <p class="mb-1"><strong>Phone:</strong> {{ $session->patient->phone }}</p>
                        <p class="mb-1"><strong>Branch:</strong> {{ $session->patient->branch->name }}</p>
                    </div>
                </div>

                <!-- Session Info -->
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
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $key => $transaction)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ format_datetime($transaction->created_at) }}</td>
                        <td>{{ bank_get_name($transaction->bank_id) }}</td>
                        <td class="text-end">{{ number_format($transaction->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center">No transactions found</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="text-end">Total Amount:</th>
                        <th class="text-end">{{ number_format($total_amount) }}</th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">Paid:</th>
                        <th class="text-end text-success">{{ number_format($paid_amount) }}</th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">Remaining:</th>
                        <th class="text-end text-danger">{{ number_format($remaining_amount) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Payment Receive Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Add New Payment</h5>
            <form action="{{ route('invoice.add-payment') }}" method="POST">
                @csrf
                <input type="hidden" name="session_id" value="{{ $session->id }}">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select" required>
                                <option value="">Select Payment Method</option>
                                <option value="0" {{ old('payment_method')=='0' ? 'selected' : '' }}>Cash</option>
                                @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}" {{ old('payment_method')=='bank'.$bank->id ? 'selected' : '' }}>
                                        Bank {{ $bank->bank_name }} | ({{ $bank->account_no }}) | {{ $bank->account_title }}
                                    </option>
                                @endforeach
                            </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Amount</label>
                        <input type="number" step="1" name="amount" max="{{ $remaining_amount }}" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Receive Payment</button>
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
@endpush
