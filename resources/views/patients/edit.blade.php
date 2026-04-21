@extends('layouts.app')

@section('title', 'Edit Patient')

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@section('content')
@php
    $referredByType = old('referred_by_type', $patient->referred_by_type);
    $referredByName = old('referred_by_name', $patient->referred_by_name);
    $referredById = old('referred_by_id', $patient->referred_by_id);
    $referredBySourceValue = old('referred_by_source', $referredBySource ?? null);
@endphp

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3>Edit Patient</h3>
        </div>
        <div class="card-body">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('patients.update', $patient->id) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-lg-2">
                    <label for="prefix" class="form-label">Prefix</label>
                    <select name="prefix" id="prefix" class="form-control" required>
                        <option value="">Select</option>
                        @foreach(['Mr.', 'Ms.', 'Mrs.'] as $p)
                            <option value="{{ $p }}" {{ old('prefix', $patient->prefix) == $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-5">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name', $patient->name) }}"
                           class="form-control" placeholder="Enter patient name" required>
                </div>

                <div class="col-lg-5">
                    <label for="guardian_name" class="form-label">Guardian Name</label>
                    <input type="text" name="guardian_name" id="guardian_name"
                           value="{{ old('guardian_name', $patient->guardian_name) }}"
                           class="form-control" placeholder="Enter guardian name" required>
                </div>

                <div class="col-lg-6">
                    <label for="age" class="form-label">Age</label>
                    <input type="number" name="age" id="age"
                           value="{{ old('age', $patient->age) }}"
                           class="form-control" placeholder="Enter age" required>
                </div>

                <div class="col-lg-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone"
                           value="{{ old('phone', $patient->phone) }}"
                           class="form-control" placeholder="Enter phone number" required>
                </div>

                <div class="col-lg-6">
                    <label for="cnic" class="form-label">CNIC</label>
                    <input type="text" name="cnic" id="cnic"
                           value="{{ old('cnic', $patient->cnic) }}"
                           class="form-control" placeholder="XXXXX-XXXXXXX-X">
                </div>

                <div class="col-lg-6">
                    <label for="gender" class="form-label">Gender</label>
                    <select name="gender" id="gender" class="form-control" required>
                        <option value="">Select Gender</option>
                        <option value="Male" {{ old('gender', $patient->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender', $patient->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ old('gender', $patient->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

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

                <div class="col-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea name="address" id="address" class="form-control" rows="2" placeholder="Enter patient address" required>{{ old('address', $patient->address) }}</textarea>
                </div>

                <div class="col-lg-6">
                    <label for="referred_by_type" class="form-label">Referred By</label>
                    <select name="referred_by_type" id="referred_by_type" class="form-control">
                        <option value="">Select Type</option>
                        <option value="body_expert_doctor" {{ $referredByType === 'body_expert_doctor' ? 'selected' : '' }}>Body Expert Doctor</option>
                        <option value="body_expert_patient" {{ $referredByType === 'body_expert_patient' ? 'selected' : '' }}>Body Expert Patient</option>
                        <option value="external_doctor" {{ $referredByType === 'external_doctor' ? 'selected' : '' }}>External Doctor</option>
                        <option value="external_patient" {{ $referredByType === 'external_patient' ? 'selected' : '' }}>External Patient</option>
                        <option value="social_media" {{ $referredByType === 'social_media' ? 'selected' : '' }}>Social Media</option>
                    </select>
                    @error('referred_by_type')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                    @error('referred_by_source')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <input type="hidden" name="referred_by_source" id="referred_by_source" value="{{ $referredBySourceValue }}">
                <input type="hidden" name="referred_by_id" id="referred_by_id" value="{{ $referredById }}">

                <div class="col-lg-6 d-none" id="internal-referrer-wrapper">
                    <label for="internal_referrer_select" class="form-label" id="internal-referrer-label">Select Referrer</label>
                    <select id="internal_referrer_select" class="form-control"></select>
                    @error('referred_by_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-lg-6 d-none" id="external-referrer-wrapper">
                    <label for="referred_by_name" class="form-label" id="external-referrer-label">Referrer Name</label>
                    <input type="text" name="referred_by_name" id="referred_by_name" class="form-control" value="{{ $referredByName }}">
                    @error('referred_by_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-lg-6 d-none" id="social-media-wrapper">
                    <label for="social_media_option" class="form-label">Social Media Platform</label>
                    <select id="social_media_option" class="form-control">
                        <option value="">Select Platform</option>
                        <option value="facebook" {{ $referredByType === 'social_media' && strtolower((string) $referredByName) === 'facebook' ? 'selected' : '' }}>Facebook</option>
                        <option value="twitter" {{ $referredByType === 'social_media' && strtolower((string) $referredByName) === 'twitter' ? 'selected' : '' }}>Twitter</option>
                        <option value="youtube" {{ $referredByType === 'social_media' && strtolower((string) $referredByName) === 'youtube' ? 'selected' : '' }}>YouTube</option>
                        <option value="instagram" {{ $referredByType === 'social_media' && strtolower((string) $referredByName) === 'instagram' ? 'selected' : '' }}>Instagram</option>
                        <option value="other" {{ $referredByType === 'social_media' && strtolower((string) $referredByName) === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('referred_by_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(function () {
            const externalOptionValue = '__external__';
            const routeSearchReferrers = "{{ route('patients.search-referrers') }}";
            const socialMediaPlatforms = ['facebook', 'twitter', 'youtube', 'instagram', 'other'];

            const referredByTypeSelect = document.getElementById('referred_by_type');
            const referredBySourceInput = document.getElementById('referred_by_source');
            const referredByIdInput = document.getElementById('referred_by_id');
            const externalReferrerWrapper = document.getElementById('external-referrer-wrapper');
            const externalReferrerLabel = document.getElementById('external-referrer-label');
            const internalReferrerWrapper = document.getElementById('internal-referrer-wrapper');
            const internalReferrerLabel = document.getElementById('internal-referrer-label');
            const socialMediaWrapper = document.getElementById('social-media-wrapper');
            const socialMediaSelect = document.getElementById('social_media_option');
            const referredByNameInput = document.getElementById('referred_by_name');
            const form = document.querySelector('form[action="{{ route('patients.update', $patient->id) }}"]');

            const initialType = @json($referredByType);
            const initialSource = @json($referredBySourceValue);
            const initialReferrer = @json($initialReferrer);
            const initialName = @json($referredByName);

            const $internalReferrerSelect = $('#internal_referrer_select');

            const isBodyExpertType = (type) => {
                return type === 'body_expert_doctor' || type === 'body_expert_patient';
            };

            const hasSocialPlatform = (value) => {
                return socialMediaPlatforms.includes(String(value || '').toLowerCase());
            };

            const internalLabelByType = (type) => {
                return type === 'body_expert_patient' ? 'Select Body Expert Patient' : 'Select Body Expert Doctor';
            };

            const externalLabelByType = (type) => {
                if (type === 'body_expert_patient' || type === 'external_patient') {
                    return 'Patient Name';
                }

                return 'Doctor Name';
            };

            const formatResultText = (item, type) => {
                if (type === 'body_expert_patient') {
                    return item.mr_number ? `${item.name} (MR#: ${item.mr_number})` : item.name;
                }

                return item.name;
            };

            $internalReferrerSelect.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Search and select',
                allowClear: true,
                ajax: {
                    url: routeSearchReferrers,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            type: referredByTypeSelect.value,
                            q: params.term || ''
                        };
                    },
                    processResults: function (response) {
                        const currentType = referredByTypeSelect.value;
                        const results = (response.data || []).map(function (item) {
                            return {
                                id: item.id,
                                text: formatResultText(item, currentType)
                            };
                        });

                        results.push({ id: externalOptionValue, text: 'External / Not in system' });

                        return { results: results };
                    }
                }
            });

            const toggleReferralFields = () => {
                const type = referredByTypeSelect.value;
                const bodyExpertType = isBodyExpertType(type);

                if (type === 'social_media') {
                    internalReferrerWrapper.classList.add('d-none');
                    externalReferrerWrapper.classList.add('d-none');
                    socialMediaWrapper.classList.remove('d-none');

                    referredBySourceInput.value = '';
                    referredByIdInput.value = '';

                    if (!socialMediaSelect.value && hasSocialPlatform(referredByNameInput.value)) {
                        socialMediaSelect.value = String(referredByNameInput.value).toLowerCase();
                    }

                    referredByNameInput.value = socialMediaSelect.value || '';
                    return;
                }

                socialMediaWrapper.classList.add('d-none');
                socialMediaSelect.value = '';

                internalReferrerWrapper.classList.toggle('d-none', !bodyExpertType);
                if (bodyExpertType) {
                    internalReferrerLabel.textContent = internalLabelByType(type);
                }

                let showExternalName = false;

                if (type === 'external_doctor' || type === 'external_patient') {
                    referredBySourceInput.value = 'external';
                    referredByIdInput.value = '';
                    showExternalName = true;
                } else if (type === '') {
                    referredBySourceInput.value = '';
                    referredByIdInput.value = '';
                    referredByNameInput.value = '';
                    showExternalName = false;
                } else if (bodyExpertType) {
                    if (!referredBySourceInput.value) {
                        referredBySourceInput.value = 'internal';
                    }

                    showExternalName = referredBySourceInput.value === 'external';
                    if (!showExternalName) {
                        referredByNameInput.value = '';
                    }
                }

                externalReferrerWrapper.classList.toggle('d-none', !showExternalName);
                if (showExternalName) {
                    externalReferrerLabel.textContent = externalLabelByType(type);
                }
            };

            socialMediaSelect.addEventListener('change', function () {
                referredByNameInput.value = this.value || '';
            });

            $internalReferrerSelect.on('select2:select', function (event) {
                const selectedValue = String(event.params.data.id);

                if (selectedValue === externalOptionValue) {
                    referredByIdInput.value = '';
                    referredBySourceInput.value = 'external';
                } else {
                    referredByIdInput.value = selectedValue;
                    referredBySourceInput.value = 'internal';
                    referredByNameInput.value = '';
                }

                toggleReferralFields();
            });

            $internalReferrerSelect.on('select2:clear', function () {
                referredByIdInput.value = '';
                referredBySourceInput.value = '';
                toggleReferralFields();
            });

            referredByTypeSelect.addEventListener('change', function () {
                const type = this.value;
                referredByIdInput.value = '';
                $internalReferrerSelect.val(null).trigger('change');

                if (type === 'external_doctor' || type === 'external_patient') {
                    referredBySourceInput.value = 'external';
                    referredByNameInput.value = '';
                } else if (isBodyExpertType(type)) {
                    referredBySourceInput.value = 'internal';
                    referredByNameInput.value = '';
                } else if (type === 'social_media') {
                    referredBySourceInput.value = '';
                    referredByIdInput.value = '';
                } else {
                    referredBySourceInput.value = '';
                    referredByNameInput.value = '';
                }

                toggleReferralFields();
            });

            if (isBodyExpertType(initialType) && initialSource === 'internal' && initialReferrer && initialReferrer.id) {
                const option = new Option(initialReferrer.text, initialReferrer.id, true, true);
                $internalReferrerSelect.append(option).trigger('change');
                referredByIdInput.value = String(initialReferrer.id);
                referredBySourceInput.value = 'internal';
            }

            if (isBodyExpertType(initialType) && initialSource === 'external') {
                const option = new Option('External / Not in system', externalOptionValue, true, true);
                $internalReferrerSelect.append(option).trigger('change');
                referredByIdInput.value = '';
                referredBySourceInput.value = 'external';
            }

            if (initialType === 'social_media') {
                const normalizedInitialName = hasSocialPlatform(initialName) ? String(initialName).toLowerCase() : '';
                socialMediaSelect.value = normalizedInitialName;
                referredByNameInput.value = normalizedInitialName;
            }

            toggleReferralFields();

            form.addEventListener('submit', function () {
                const type = referredByTypeSelect.value;

                if (!isBodyExpertType(type)) {
                    referredByIdInput.value = '';
                }

                if (type === 'social_media') {
                    referredBySourceInput.value = '';
                    referredByIdInput.value = '';
                    referredByNameInput.value = socialMediaSelect.value || '';
                    return;
                }

                if (type === '') {
                    referredBySourceInput.value = '';
                    referredByNameInput.value = '';
                }
            });
        });
    </script>
@endpush
