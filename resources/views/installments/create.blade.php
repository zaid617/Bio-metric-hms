@extends('layouts.app')

@section('title', 'Add Installment')

@section('content')

    <x-page-title title="Add Installment" subtitle="Installment details for treatment session" />

    <div class="row">
        <!-- 🔵 Column 1: Installment Form -->
        <div class="col-md-6 mb-4">
            <div class="card border-success shadow-sm">
                <div class="card-header bg-success text-white fw-semibold">Add Installment</div>
                <div class="card-body">
                    {{-- Display Validation Errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('installments.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="session_id" value="{{ $session->id }}">

                        <div class="mb-3">
                            <label for="amount" class="form-label fw-semibold">Installment Amount</label>
                            <input type="number" name="amount" class="form-control" required
                                max="{{ $session->remainingAmount() }}"
                                placeholder="Remaining: {{ $session->remainingAmount() }}">
                        </div>

                        <div class="mb-3">
                            <label for="payment_date" class="form-label fw-semibold">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" required>
                        </div>

                        <div class="d-md-flex d-grid align-items-center gap-3 mt-4">
                            <button type="submit" class="btn btn-success px-4">💾 Save Installment</button>
                            <a href="{{ route('treatment-sessions.index') }}" class="btn btn-secondary px-4">🔙 Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 🟦 Column 2: Session Info -->
        <div class="col-md-6 mb-4">
            <div class="card border-primary shadow-sm">
                <div class="card-header bg-primary text-white fw-semibold">Treatment Session Info</div>
                <div class="card-body bg-light">

                    <!-- Session Details -->
                    <div class="row mb-2">
                        <div class="col-6"><strong>Session ID:</strong> {{ $session->id }}</div>
                        <div class="col-6"><strong>Checkup ID:</strong> {{ $session->checkup_id }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Patient Name:</strong> {{ $session->patient->name ?? 'N/A' }}</div>
                        <div class="col-6"><strong>Doctor Name:</strong> {{ $session->doctor->name ?? 'N/A' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Session Fee:</strong> Rs. {{ number_format($session->session_fee,0) }}</div>
                        <div class="col-6"><strong>Total Paid:</strong> Rs. {{ number_format($session->totalPaid(),0) }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Remaining:</strong> Rs. {{ number_format($session->remainingAmount(),0) }}</div>
                    </div>

                    <!-- Installment List -->
                    <div class="mt-4">
                        <h6 class="mb-2 text-dark fw-bold">Installment Details</h6>
                        <ul class="list-group">
                            @forelse($session->installments as $installment)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ \Carbon\Carbon::parse($installment->payment_date)->format('d M Y') }}
                                    <span class="badge bg-success rounded-pill">Rs. {{ $installment->amount }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No installments added yet.</li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Session Schedule -->
                    <div class="mt-4">
                        <h6 class="mb-2 text-dark fw-bold">Session Schedule</h6>
                        <ul class="list-group">
                            @forelse ($session->sessionTimes as $entry)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ \Carbon\Carbon::parse($entry->session_datetime)->format('d M Y - h:i A') }}
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No sessions scheduled yet.</li>
                            @endforelse
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
