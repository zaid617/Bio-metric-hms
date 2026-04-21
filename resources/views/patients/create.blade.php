@extends('layouts.app')

@section('title', 'Add Patient')

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@section('content')
<x-page-title title="Patient" subtitle="Add New Patient" />

@php
    $referredByType = old('referred_by_type');
    $referredByName = old('referred_by_name');
    $referredById = old('referred_by_id');
    $referredBySourceValue = old('referred_by_source', $referredBySource ?? null);
@endphp

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="mb-4">Patient Information</h5>

                <form method="POST" action="{{ route('patients.store') }}" class="row g-3">
                    @csrf

                    <div class="col-md-2">
                        <label class="form-label">Prefix</label>
                        <select name="prefix" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Mr." {{ old('prefix') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                            <option value="Ms." {{ old('prefix') == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                            <option value="Mrs." {{ old('prefix') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Father / Husband Name</label>
                        <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Age</label>
                        <input type="number" name="age" class="form-control" value="{{ old('age') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">CNIC</label>
                        <input type="text" name="cnic" class="form-control" value="{{ old('cnic') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" required>{{ old('address') }}</textarea>
                    </div>

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

                    <div class="col-md-6">
                        <label for="referred_by_type" class="form-label">Referred By</label>
                        <select name="referred_by_type" id="referred_by_type" class="form-select">
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

                    <div class="col-md-6 d-none" id="internal-referrer-wrapper">
                        <label for="internal_referrer_select" class="form-label" id="internal-referrer-label">Select Referrer</label>
                        <select id="internal_referrer_select" class="form-select"></select>
                        @error('referred_by_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 d-none" id="external-referrer-wrapper">
                        <label for="referred_by_name" class="form-label" id="external-referrer-label">Referrer Name</label>
                        <input type="text" name="referred_by_name" id="referred_by_name" class="form-control" value="{{ $referredByName }}">
                        @error('referred_by_name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 d-none" id="social-media-wrapper">
                        <label for="social_media_option" class="form-label">Social Media Platform</label>
                        <select id="social_media_option" class="form-select">
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
            const form = document.querySelector('form[action="{{ route('patients.store') }}"]');

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
