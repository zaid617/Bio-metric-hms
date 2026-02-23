@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <h4>💰 Income Report</h4>

    <!-- 🔍 Filter Form -->
    <form method="GET" action="{{ route('income.report') }}" class="row g-3 mb-3">
        <div class="col-md-3">
            <label>Payment Type:</label>
            <select name="payment_type" class="form-control">
                <option value="">All</option>
                @foreach($paymentTypes as $key => $value)
                    <option value="{{ $key }}" {{ $paymentType == $key ? 'selected' : '' }}>{{ $value }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label>From Date:</label>
            <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control">
        </div>

        <div class="col-md-3">
            <label>To Date:</label>
            <input type="date" name="to_date" value="{{ $toDate }}" class="form-control">
        </div>

        <div class="col-md-3">
            <label>Search by Patient Name:</label>
            <input type="text" name="search_user" placeholder="Enter patient name..." value="{{ $searchUser }}" class="form-control">
        </div>

        <div class="col-md-12 mt-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('income.report') }}" class="btn btn-secondary ms-2">Reset</a>
        </div>
    </form>

    <!-- 📋 Income Table -->
    <table class="table table-bordered table-striped mt-3">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Patient Name</th>
                <th>Payment Type</th>
                <th>Payment Method</th>
                <th>Amount</th>
                <th>Branch ID</th>
                <th>Remarks</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incomes as $index => $income)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $income->patient_name ?? '-' }}</td>
                    <td>{{ $paymentTypes[$income->payment_type] ?? 'N/A' }}</td>
                    <td>{{ $income->payment_method ?? '-' }}</td>
                    <td>{{ number_format($income->amount, 2) }}</td>
                    <td>{{ $income->branch_id ?? '-' }}</td>
                    <td>{{ $income->remx ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($income->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>
@endsection
