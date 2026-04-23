@extends('layouts.app')

@section('title')
    Patient History
@endsection

@section('content')

<x-page-title title="Patient History" subtitle="All Checkups of {{ $patient->name }}" />

<div class="card">
    <div class="card-body">

        <h4>Patient: {{ $patient->name }}</h4>
        <p><strong>Total Checkups:</strong> {{ $history->count() }}</p>

        <div class="table-responsive mt-3">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Checkup Date</th>
                        <th>Type</th>
                        <th>Doctor</th>
                        <th>Branch</th>
                        <th>Fee</th>
                        <th>Discount (%)</th>
                        <th>Paid Amount</th>
                        <th>Pending Amount</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $checkup)
                        @php
                            $pendingAmount = (float) ($checkup->pending_amount_resolved ?? $checkup->pending_amount ?? 0);
                            $paidAmount = (float) ($checkup->paid_amount ?? 0);
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ \Carbon\Carbon::parse($checkup->created_at)->format('d-m-Y') }}</td>
                            <td>{{ $checkup->consultation_type_display ?? ($checkup->consultation_type ?? 'Appointment') }}</td>
                            <td>{{ $checkup->doctor_name }}</td>
                            <td>{{ $checkup->branch_name }}</td>
                            <td>Rs. {{ number_format($checkup->fee, 2) }}</td>
                            <td>{{ number_format((float) ($checkup->discount ?? 0), 2) }}%</td>
                            <td>Rs. {{ number_format($paidAmount, 2) }}</td>
                            <td>Rs. {{ number_format($pendingAmount, 2) }}</td>
                            <td>
                                @if ($pendingAmount <= 0)
                                    <span class="badge bg-success">Fully Paid</span>
                                @elseif($paidAmount > 0)
                                    <span class="badge bg-warning text-dark">Partially Paid</span>
                                @else
                                    <span class="badge bg-danger">Unpaid</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">No checkups found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>

@endsection
