@extends('layouts.app')

@section('title')
    Bank Ledger Report
@endsection

@push('css')
    {{-- DataTables CSS (optional) --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        .table-responsive {
            overflow: visible; /* Dropdowns overflow fix */
        }
    </style>
@endpush

@section('content')
<div class="container mt-4">

    <h4> Bank Ledger Report</h4>

    <!-- 🔍 Filter Form -->
    <form action="{{ route('bankledger.filter') }}" method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
            <label>Bank</label>
            <select name="bank_id" class="form-control">
                <option value="all">All Banks</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank->id }}" {{ isset($bank_id) && $bank_id == $bank->id ? 'selected' : '' }}>
                        {{ $bank->bank_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label>From Date</label>
            <input type="date" name="from_date" value="{{ $from_date ?? '' }}" class="form-control">
        </div>

        <div class="col-md-3">
            <label>To Date</label>
            <input type="date" name="to_date" value="{{ $to_date ?? '' }}" class="form-control">
        </div>

        <div class="col-md-3 align-self-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <!-- 📋 Ledger Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="bankLedgerTable">
            <thead class="table-dark">
                <tr>
                    <th>Date</th>
                    <th>Payment Method</th>
                    <th>Bank</th>
                    <th>Type (+ / -)</th>
                    <th>Amount</th>
                    <th>Post Bank Balance</th>
                    <th>Remarks</th>
                </tr>
            </thead>

            <tbody>
                @php
                    $depositTotal = 0;
                    $withdrawTotal = 0;

                    $openingTransactions = $transactions->where('payment_type', 0);
                    $otherTransactions   = $transactions->where('payment_type', '!=', 0)->sortBy('id');
                @endphp

                {{-- Opening Balance --}}
                @foreach($openingTransactions as $t)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d-m-Y') }}</td>
                        <td>{{ ucfirst($t->payment_method ?? 'N/A') }}</td>
                        <td>{{ $t->bank_name ?? 'Bank #'.$t->bank_id }}</td>
                        <td>Opening Balance</td>
                        <td class="text-secondary">{{ number_format($t->amount, 2) }}</td>
                        <td>{{ number_format($t->post_bank_balance ?? 0, 2) }}</td>
                        <td>{{ $t->Remx ?? '—' }}</td>
                    </tr>
                @endforeach

                {{-- Other transactions --}}
                @foreach($otherTransactions as $t)
                    @php
                        if($t->type == '+') $depositTotal += (float)$t->amount;
                        if($t->type == '-') $withdrawTotal += (float)$t->amount;
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d-m-Y') }}</td>
                        <td>{{ ucfirst($t->payment_method ?? 'N/A') }}</td>
                        <td>{{ $t->bank_name ?? 'Bank #'.$t->bank_id }}</td>
                        <td>{{ $t->type == '+' ? 'Deposit' : 'Withdrawal' }}</td>
                        <td>
                            @if($t->type == '+')
                                <span class="text-success">+ {{ number_format($t->amount, 2) }}</span>
                            @else
                                <span class="text-danger">- {{ number_format($t->amount, 2) }}</span>
                            @endif
                        </td>
                        <td>{{ number_format($t->post_bank_balance ?? 0, 2) }}</td>
                        <td>{{ $t->Remx ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>

            {{-- Totals --}}
            @if($otherTransactions->count() > 0)
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="4" class="text-end">Total Deposit:</td>
                    <td class="text-success">+ {{ number_format($depositTotal, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end">Total Withdrawal:</td>
                    <td class="text-danger">- {{ number_format($withdrawTotal, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end">Net Balance:</td>
                    <td class="{{ ($depositTotal - $withdrawTotal) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($depositTotal - $withdrawTotal, 2) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection

@push('script')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS (optional) -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Core Plugins -->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script>
        // Optional: Initialize DataTable for future use
        $(document).ready(function() {
            $('#bankLedgerTable').DataTable({
                responsive: true,
                autoWidth: false,
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100]
            });
        });
    </script>
@endpush
