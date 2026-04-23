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
                                    {{ ($patient->mr ?? '-') . ' | ' . $patient->name }}
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

                    <div class="col-md-6">
                        <label for="consultation_type" class="form-label">Consultation Type</label>
                        <select name="consultation_type" id="consultation_type" class="form-select" required>
                            @foreach(($consultationTypes ?? ['Appointment', 'Enrollment']) as $type)
                                <option value="{{ $type }}" {{ (($consultation->consultation_type ?? 'Appointment') === $type) ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" name="description" id="description" class="form-control"
                               value="{{ old('description', $consultation->description) }}" placeholder="e.g. Follow-up, Initial consultation...">
                    </div>

                    <!-- Fee -->
                    <div class="col-md-6">
                        <label for="fee" class="form-label">Consultation Fee (Rs)</label>
                        <input type="number" name="fee" id="fee" class="form-control"
                               step="0.01" min="0" value="{{ old('fee', $consultation->fee) }}" required>
                    </div>

                    <!-- Discount -->
                    <div class="col-md-6">
                           <label for="discount" class="form-label">Discount (%)</label>
                        <input type="number" name="discount" id="discount" class="form-control"
                               step="0.01" min="0" max="100" value="{{ old('discount', $consultation->discount ?? 0) }}">
                    </div>

                    <!-- Paid Amount -->
                    <div class="col-md-6">
                        <label for="paid_amount" class="form-label">Paid Amount (Rs)</label>
                        <input type="number" name="paid_amount" id="paid_amount" class="form-control"
                               step="0.01" min="0" value="{{ old('paid_amount', $consultation->paid_amount ?? 0) }}">
                    </div>

                    <div class="col-md-6">
                        <label for="pending_amount_preview" class="form-label">Pending Amount</label>
                        <input type="number" id="pending_amount_preview" class="form-control"
                               value="{{ old('pending_amount_preview', $consultation->pending_amount ?? 0) }}" step="0.01" readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-select">
                            <option value="">Select Payment Method</option>
                            <option value="0" {{ (string) old('payment_method', $consultation->payment_method) === '0' ? 'selected' : '' }}>Cash</option>
                            @foreach (($banks ?? []) as $bank)
                                <option value="{{ $bank->id }}" {{ (string) old('payment_method', $consultation->payment_method) === (string) $bank->id ? 'selected' : '' }}>
                                    Bank {{ $bank->bank_name }} | ({{ $bank->account_no }}) | {{ $bank->account_title }}
                                </option>
                            @endforeach
                        </select>
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
<script>
    (function () {
        const feeInput = document.getElementById('fee');
        const discountInput = document.getElementById('discount');
        const paidInput = document.getElementById('paid_amount');
        const pendingInput = document.getElementById('pending_amount_preview');
        const form = document.querySelector('form[action="{{ url('/consultations/' . $consultation->id) }}"]');

        const recalcPending = () => {
            const fee = parseFloat(feeInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            const paid = parseFloat(paidInput.value) || 0;
            const discountAmount = fee * (discount / 100);
            const maxPayable = Math.max(0, fee - discountAmount);
            const pending = Math.max(0, maxPayable - paid);
            pendingInput.value = pending.toFixed(2);
        };

        ['input', 'change'].forEach((eventName) => {
            feeInput.addEventListener(eventName, recalcPending);
            discountInput.addEventListener(eventName, recalcPending);
            paidInput.addEventListener(eventName, recalcPending);
        });

        form.addEventListener('submit', function (event) {
            const fee = parseFloat(feeInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            const paid = parseFloat(paidInput.value) || 0;

            if (discount > 100) {
                event.preventDefault();
                alert('Discount cannot exceed 100%.');
                return;
            }

            const discountAmount = fee * (discount / 100);
            const maxPayable = Math.max(0, fee - discountAmount);

            if (paid > maxPayable) {
                event.preventDefault();
                alert('Paid Amount cannot exceed Total after Discount.');
            }
        });

        recalcPending();
    })();
</script>
<script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
