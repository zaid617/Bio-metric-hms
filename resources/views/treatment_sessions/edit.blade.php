@extends('layouts.app')
@section('title')
    Edit Treatment Session
@endsection
@section('content')
    <x-page-title title="Treatment Session" subtitle="Edit Session" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="mb-4">Edit Treatment Session</h5>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('treatment-sessions.update', $session->id) }}" class="row g-3">
                        @csrf
                        @method('PUT')

                        {{-- Patient --}}
                        <div class="col-md-6">
                            <label for="patient_id" class="form-label">Patient</label>
                            <select name="patient_id" id="patient_id" class="form-select" required>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" {{ $patient->id == old('patient_id', $session->patient_id) ? 'selected' : '' }}>
                                        {{ $patient->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Doctor --}}
                        <div class="col-md-6">
                            <label for="doctor_id" class="form-label">Doctor</label>
                            <select name="doctor_id" id="doctor_id" class="form-select" required>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}" {{ $doctor->id == old('doctor_id', $session->doctor_id) ? 'selected' : '' }}>
                                        {{ $doctor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Checkup ID --}}
                        <div class="col-md-6">
                            <label for="checkup_id" class="form-label">Checkup ID</label>
                            <input type="number" name="checkup_id" id="checkup_id" 
                                   class="form-control" value="{{ old('checkup_id', $session->checkup_id) }}" required>
                        </div>

                        {{-- Session Fee --}}
                        <div class="col-md-6">
                            <label for="session_fee" class="form-label">Fee (Rs)</label>
                            <input type="number" name="session_fee" id="session_fee" 
                                   class="form-control" value="{{ old('session_fee', $session->session_fee) }}" step="0.01" required>
                        </div>

                        {{-- Paid Amount --}}
                        <div class="col-md-6">
                            <label for="paid_amount" class="form-label">Paid Amount (Rs)</label>
                            <input type="number" name="paid_amount" id="paid_amount" 
                                   class="form-control" value="{{ old('paid_amount', $session->paid_amount) }}" step="0.01" required>
                        </div>

                        {{-- Status --}}
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="scheduled" {{ old('status', $session->status) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="completed" {{ old('status', $session->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="missed" {{ old('status', $session->status) == 'missed' ? 'selected' : '' }}>Missed</option>
                            </select>
                        </div>

                        {{-- Payment Status --}}
                        <div class="col-md-6">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select name="payment_status" id="payment_status" class="form-select" required>
                                <option value="unpaid" {{ old('payment_status', $session->payment_status) == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="paid" {{ old('payment_status', $session->payment_status) == 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>

                        {{-- Submit --}}
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-4">Update Session</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!--plugins-->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
