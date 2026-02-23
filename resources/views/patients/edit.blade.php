@extends('layouts.app')

@section('title', 'Edit Patient')

@section('content')
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3>Edit Patient</h3>
        </div>
        <div class="card-body">

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Edit Patient Form --}}
            <form method="POST" action="{{ route('patients.update', $patient->id) }}" class="row g-3">
                @csrf
                @method('PUT')

                {{-- Prefix --}}
                <div class="col-lg-2">
                    <label for="prefix" class="form-label">Prefix</label>
                    <select name="prefix" id="prefix" class="form-control" required>
                        <option value="">Select</option>
                        @foreach(['Mr.', 'Ms.', 'Mrs.'] as $p)
                            <option value="{{ $p }}" {{ old('prefix', $patient->prefix) == $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Name --}}
                <div class="col-lg-5">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name', $patient->name) }}"
                           class="form-control" placeholder="Enter patient name" required>
                </div>

                {{-- Guardian Name --}}
                <div class="col-lg-5">
                    <label for="guardian_name" class="form-label">Guardian Name</label>
                    <input type="text" name="guardian_name" id="guardian_name"
                           value="{{ old('guardian_name', $patient->guardian_name) }}"
                           class="form-control" placeholder="Enter guardian name" required>
                </div>

                {{-- Age --}}
                <div class="col-lg-6">
                    <label for="age" class="form-label">Age</label>
                    <input type="number" name="age" id="age"
                           value="{{ old('age', $patient->age) }}"
                           class="form-control" placeholder="Enter age" required>
                </div>

                {{-- Phone --}}
                <div class="col-lg-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone"
                           value="{{ old('phone', $patient->phone) }}"
                           class="form-control" placeholder="Enter phone number" required>
                </div>

                {{-- CNIC --}}
                <div class="col-lg-6">
                    <label for="cnic" class="form-label">CNIC</label>
                    <input type="text" name="cnic" id="cnic"
                           value="{{ old('cnic', $patient->cnic) }}"
                           class="form-control" placeholder="XXXXX-XXXXXXX-X">
                </div>

                {{-- Gender --}}
                <div class="col-lg-6">
                    <label for="gender" class="form-label">Gender</label>
                    <select name="gender" id="gender" class="form-control" required>
                        <option value="">Select Gender</option>
                        <option value="Male" {{ old('gender', $patient->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender', $patient->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ old('gender', $patient->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                {{-- Branch --}}
                <div class="col-lg-6">
                    <label for="branch_id" class="form-label">Branch</label>
                    <select name="branch_id" id="branch_id" class="form-control" required>
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $patient->branch_id) == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Address --}}
                <div class="col-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea name="address" id="address" class="form-control" rows="2" placeholder="Enter patient address" required>{{ old('address', $patient->address) }}</textarea>
                </div>

                {{-- Referred By --}}
                @php
                    $typeValue = old('type_select', $patient->type_select);
                    $subValue  = old('sub_select', $patient->sub_select);
                @endphp
                <div class="col-lg-6">
                    <label for="type_select" class="form-label">Referred By</label>
                    <div class="d-flex gap-2">
                        <select name="type_select" id="type_select" class="form-control">
                            <option value="">Select Type</option>
                            <option value="doctor" {{ $typeValue === 'doctor' ? 'selected' : '' }}>Doctor</option>
                            <option value="patient" {{ $typeValue === 'patient' ? 'selected' : '' }}>Patient</option>
                            <option value="social" {{ $typeValue === 'social' ? 'selected' : '' }}>Social Media</option>
                        </select>

                        <select name="sub_select" id="sub_select" class="form-control {{ $subValue ? '' : 'd-none' }}">
                            <option value="">{{ $subValue ?? 'Select' }}</option>
                        </select>
                    </div>
                </div>

                {{-- Submit Buttons --}}
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Update Patient</button>
                    <a href="{{ route('patients.index') }}" class="btn btn-secondary">Cancel</a>
                </div>

            </form>

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

    function populateSubSelect(value, oldValue = null) {
        subSelect.classList.remove('d-none');
        subSelect.innerHTML = '<option value="">Select</option>';

        if(value === '') {
            subSelect.classList.add('d-none');
            return;
        }

        if(value === 'doctor') {
            doctors.forEach(doc => {
                const selected = (oldValue === doc) ? 'selected' : '';
                subSelect.innerHTML += `<option value="${doc}" ${selected}>${doc}</option>`;
            });
        }

        if(value === 'patient') {
            patients.forEach(pat => {
                const selected = (oldValue === pat) ? 'selected' : '';
                subSelect.innerHTML += `<option value="${pat}" ${selected}>${pat}</option>`;
            });
        }

        if(value === 'social') {
            ['WhatsApp', 'Facebook', 'Twitter'].forEach(platform => {
                const selected = (oldValue === platform) ? 'selected' : '';
                subSelect.innerHTML += `<option value="${platform}" ${selected}>${platform}</option>`;
            });
        }
    }

    populateSubSelect(typeSelect.value, "{{ $subValue }}");

    typeSelect.addEventListener('change', function () {
        populateSubSelect(this.value);
    });
</script>
@endpush
