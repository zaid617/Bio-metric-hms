@extends('layouts.app')

@section('title', 'Add Patient')

@section('content')
<x-page-title title="Patient" subtitle="Add New Patient" />

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="mb-4">Patient Information</h5>

                <form method="POST" action="{{ route('patients.store') }}" class="row g-3">
                    @csrf


                    {{-- Prefix --}}
<div class="col-md-2">
    <label class="form-label">Prefix</label>
    <select name="prefix" class="form-select" required>
        <option value="">Select</option>
        <option value="Mr." {{ old('prefix') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
        <option value="Ms." {{ old('prefix') == 'Ms.' ? 'selected' : '' }}>Ms.</option>
        <option value="Mrs." {{ old('prefix') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
    </select>
</div>

                    {{-- Name --}}
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>

                    {{-- Guardian --}}
                    <div class="col-md-6">
                        <label class="form-label">Father / Husband Name</label>
                        <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}" required>
                    </div>

                    {{-- Age --}}
                    <div class="col-md-4">
                        <label class="form-label">Age</label>
                        <input type="number" name="age" class="form-control" value="{{ old('age') }}" required>
                    </div>

                    {{-- Gender --}}
                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    {{-- Phone --}}
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                    </div>

                    {{-- CNIC --}}
                    <div class="col-md-6">
                        <label class="form-label">CNIC</label>
                        <input type="text" name="cnic" class="form-control" value="{{ old('cnic') }}">
                    </div>

                    {{-- Address --}}
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" required>{{ old('address') }}</textarea>
                    </div>

                    {{-- Branch --}}
                    @if(auth()->user()->role == 'admin')
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select" required>
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                    @endif

                    {{-- Referred By --}}
                    <div class="col-md-6">
                        <label class="form-label">Referred By</label>
                        <div class="d-flex gap-2">
                            <select name="type_select" id="type_select" class="form-select">
                                <option value="">Select Type</option>
                                <option value="doctor" {{ old('type_select') == 'doctor' ? 'selected' : '' }}>Doctor</option>
                                <option value="patient" {{ old('type_select') == 'patient' ? 'selected' : '' }}>Patient</option>
                                <option value="social" {{ old('type_select') == 'social' ? 'selected' : '' }}>Social Media</option>
                            </select>

                            <select name="sub_select" id="sub_select" class="form-select {{ old('sub_select') ? '' : 'd-none' }}">
                                <option value="">{{ old('sub_select') ? old('sub_select') : 'Select' }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="col-12 text-end mt-3">
                        <button class="btn btn-primary px-4">Save Patient</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    const typeSelect = document.getElementById('type_select');
    const subSelect  = document.getElementById('sub_select');

    const doctors  = @json($doctors ?? []);
    const patients = @json($patients ?? []);

    typeSelect.addEventListener('change', function () {

        subSelect.classList.remove('d-none');
        subSelect.innerHTML = '<option value="">Select</option>';

        if (this.value === '') {
            subSelect.classList.add('d-none');
            return;
        }

        if (this.value === 'doctor') {
            doctors.forEach(doc => {
                subSelect.innerHTML +=
                    `<option value="${doc.name}">${doc.name}</option>`;
            });
        }

        if (this.value === 'patient') {
            patients.forEach(pat => {
                subSelect.innerHTML +=
                    `<option value="${pat.name}">${pat.name}</option>`;
            });
        }

        if (this.value === 'social') {
            ['WhatsApp', 'Facebook', 'Twitter'].forEach(platform => {
                subSelect.innerHTML += `<option value="${platform}">${platform}</option>`;
            });
        }

        // Preserve old value if form is submitted and validation fails
        @if(old('sub_select'))
            subSelect.value = "{{ old('sub_select') }}";
        @endif
    });
</script>
@endpush
