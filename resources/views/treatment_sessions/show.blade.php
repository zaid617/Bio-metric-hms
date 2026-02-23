@extends('layouts.app')

@section('title', 'Treatment Session Details')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Treatment Session Details</h4>
            <div class="d-flex align-items-center">
                {{-- Theme Toggle --}}
                <button id="themeToggle" class="btn btn-outline-primary btn-sm me-2">
                    <i class="bi bi-moon"></i> Toggle Theme
                </button>
                <a href="{{ route('treatment-sessions.index') }}" class="btn btn-secondary btn-sm">Back</a>
            </div>
        </div>
        
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr>
                        <th>Doctor</th>
                        <td>{{ $session->doctor->name ?? 'N/A' }}</td>

                        <th>Patient</th>
                        <td>{{ $session->patient->name ?? 'N/A' }}</td>

                        <th>Checkup Date</th>
                        <td>{{ $session->checkup->date ?? 'N/A' }}</td>

                        <th>Session Fee</th>
                        <td>{{ number_format($session->session_fee, 2) }}</td>

                        <th>Total Sessions</th>
                        <td>{{ $session->sessionTimes->count() }}</td>

                        <th>Paid Amount</th>
                        <td>{{ number_format($session->totalPaid(), 2) }}</td>

                        <th>Dues Amount</th>
                        <td>{{ number_format($session->remainingAmount(), 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <h5 class="mt-4">Session Times</h5>
            <ul class="list-group mb-3">
                @forelse($session->sessionTimes as $time)
                    <li class="list-group-item">
                        {{ \Carbon\Carbon::parse($time->session_datetime)->format('d M Y, h:i A') }}
                    </li>
                @empty
                    <li class="list-group-item text-muted">No sessions scheduled</li>
                @endforelse
            </ul>

            <h5>Installments</h5>
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($session->installments as $installment)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($installment->payment_date)->format('d M Y') }}</td>
                            <td>{{ number_format($installment->amount, 2) }}</td>
                            <td>{{ ucfirst($installment->payment_method) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted text-center">No installments</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="{{ URL::asset('build/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/plugins/metismenu/metisMenu.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/plugins/simplebar/css/simplebar.min.css') }}" rel="stylesheet">
@endpush

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('themeToggle');
        if(btn){
            btn.addEventListener('click', function() {
                document.body.classList.toggle('bg-dark');
                document.body.classList.toggle('text-white');
                localStorage.setItem('theme', document.body.classList.contains('bg-dark') ? 'dark' : 'light');
            });
        }

        if(localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('bg-dark', 'text-white');
        }
    });
    </script>
@endpush
