@extends('layouts.app')

@section('title')
    Add Consultation
@endsection

@push('css')
    {{-- Select2 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@section('content')
    <x-page-title title="Consultations" subtitle="Add New Consultation" />

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('consultations.store') }}" class="row g-3">
                        @csrf

                        <!-- Patient Dropdown -->
                        <div class="col-md-12">
                            <label for="patient_id" class="form-label">Patient Name</label>
                            <select name="patient_id" id="patient_id" class="form-select" required>
                                <option value="">Select Patient</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" 
                                        {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                                        {{ $patient->mr }} | {{ $patient->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Doctor Dropdown -->
                        <div class="col-md-12">
                            <label for="doctor_id" class="form-label">Doctor Name</label>
                            <select name="doctor_id" id="doctor_id" class="form-select" required>
                                <option value="">Select Doctor</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                                @endforeach
                            </select>
                        </div>


                        <!-- Referred By Dropdown -->
<div class="col-md-12">
    <label for="referred_by" class="form-label">Referred By</label>
    <select name="referred_by" id="referred_by" class="form-select" required>
        <option value="">Select Referring Doctor</option>
        @foreach($doctors as $doctor)
            <option value="{{ $doctor->id }}" {{ old('referred_by') == $doctor->id ? 'selected' : '' }}>
                {{ $doctor->name }}
            </option>
        @endforeach
    </select>
</div>


                        <!-- Consultation Fee -->
                        <div class="col-md-3">
                            <label for="fee" class="form-label">Consultation Fee</label>
                            <input type="number" name="fee" id="fee" class="form-control" value="{{ old('fee') ?? 0 }}" readonly>
                        </div>

                        <!-- Paid Amount -->
                        <div class="col-md-3">
                            <label for="paid_amount" class="form-label">Paid Amount</label>
                            <input type="number" name="paid_amount" id="paid_amount" class="form-control" value="{{ old('paid_amount') ?? 0 }}" step="0.01">
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select">
                                <option value="">Select Payment Method</option>
                                <option value="0" {{ old('payment_method')=='0' ? 'selected' : '' }}>Cash</option>
                                @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}" {{ old('payment_method')=='bank'.$bank->id ? 'selected' : '' }}>
                                        Bank {{ $bank->bank_name }} | ({{ $bank->account_no }}) | {{ $bank->account_title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary px-4">Add Consultation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    {{-- jQuery + Select2 --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    {{-- Layout Plugins --}}
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#patient_id, #doctor_id, #payment_method,#referred_by').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Fetch fee when patient changes
            $('#patient_id').on('change', function() {
                var patientId = $(this).val();
                if (patientId) {
                    $.get('/patients/' + patientId + '/checkup-fee', function(data) {
                        $('#fee').val(data.fee);
                    });
                } else {
                    $('#fee').val(0);
                }
            });

            // Trigger change on page load to auto-load fee if patient_id is in query
            $('#patient_id').trigger('change');
        });
    </script>
@endpush
