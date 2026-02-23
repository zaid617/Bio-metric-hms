@extends('layouts.app')

@section('title')
    Edit Consultation
@endsection

@section('content')
<x-page-title title="Consultations" subtitle="Edit Consultation" />

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="mb-4">Edit Consultation Information</h5>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ url('/consultations/' . $consultation->id) }}" class="row g-3">
                    @csrf
                    @method('PUT')

                    <!-- Patient Dropdown -->
                    <div class="col-md-6">
                        <label for="patient_id" class="form-label">Patient</label>
                        <select name="patient_id" id="patient_id" class="form-select" required>
                            <option value="">Select Patient</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" {{ $consultation->patient_id == $patient->id ? 'selected' : '' }}>
                                    {{ $patient->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Doctor Dropdown -->
                    <div class="col-md-6">
                        <label for="doctor_id" class="form-label">Doctor</label>
                        <select name="doctor_id" id="doctor_id" class="form-select" required>
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}" {{ $consultation->doctor_id == $doctor->id ? 'selected' : '' }}>
                                    {{ $doctor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fee -->
                    <div class="col-md-6">
                        <label for="fee" class="form-label">Consultation Fee (Rs)</label>
                        <input type="number" name="fee" id="fee" class="form-control"
                               step="0.01" value="{{ $consultation->fee }}" required>
                    </div>

                    <!-- Paid Amount -->
                    <div class="col-md-6">
                        <label for="paid_amount" class="form-label">Paid Amount (Rs)</label>
                        <input type="number" name="paid_amount" id="paid_amount" class="form-control"
                               step="0.01" value="{{ $consultation->paid_amount ?? 0 }}">
                    </div>

                    <!-- Submit -->
                    <div class="col-12 mt-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Update Consultation</button>
                            <a href="{{ url('/consultations') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>

                </form>
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
