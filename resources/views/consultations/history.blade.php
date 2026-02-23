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
                        <th>Doctor</th>
                        <th>Branch</th>
                        <th>Fee</th>
                        <th>Paid Amount</th>
                        <th>Checkup Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $checkup)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ \Carbon\Carbon::parse($checkup->created_at)->format('d-m-Y') }}</td>
                            <td>{{ $checkup->doctor_name }}</td>
                            <td>{{ $checkup->branch_name }}</td>
                            <td>Rs. {{ number_format($checkup->fee, 2) }}</td>
                            <td>Rs. {{ number_format($checkup->paid_amount, 2) }}</td>
                            <td>
                                @php 
                                    $status = (int)($checkup->checkup_status ?? 0); 
                                @endphp
                                @if ($status === 0)
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($status === 1)
                                    <span class="badge bg-success">Completed</span>
                                @elseif($status === 2)
                                    <span class="badge bg-danger">Cancelled</span>
                                @else
                                    <span class="badge bg-secondary">Unknown</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No checkups found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>

@endsection
