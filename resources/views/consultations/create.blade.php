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

    @php
        $referredByType = old('referred_by_type');
        $referredByName = old('referred_by_name');
        $referredById = old('referred_by_id');
        $referredBySourceValue = old('referred_by_source', $referredBySource ?? null);
        $consultationType = old('consultation_type', 'Appointment');
    @endphp

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

                        <div class="col-md-12">
                            <label for="consultation_type" class="form-label">Consultation Type</label>
                            <select name="consultation_type" id="consultation_type" class="form-select" required>
                                @foreach(($consultationTypes ?? ['Appointment', 'Enrollment']) as $type)
                                    <option value="{{ $type }}" {{ $consultationType === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('consultation_type')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-md-12">
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

                        <div class="col-md-12 d-none" id="internal-referrer-wrapper">
                            <label for="internal_referrer_select" class="form-label" id="internal-referrer-label">Select Referrer</label>
                            <select id="internal_referrer_select" class="form-select"></select>
                            @error('referred_by_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-md-12 d-none" id="external-referrer-wrapper">
                            <label for="referred_by_name" class="form-label" id="external-referrer-label">Referrer Name</label>
                            <input type="text" name="referred_by_name" id="referred_by_name" class="form-control" value="{{ $referredByName }}">
                            @error('referred_by_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-md-12 d-none" id="social-media-wrapper">
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


                        <!-- Description -->
                        <div class="col-md-12">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" name="description" id="description" class="form-control" value="{{ old('description') }}" placeholder="e.g. Follow-up, Initial consultation...">
                        </div>

                        <!-- Consultation Fee -->
                        <div class="col-md-3">
                            <label for="fee" class="form-label">Fee</label>
                            <input type="number" name="fee" id="fee" class="form-control" value="{{ old('fee') ?? 0 }}">
                        </div>

                        <!-- Discount -->
                        <div class="col-md-2">
                            <label for="discount" class="form-label">Discount (%)</label>
                            <input type="number" name="discount" id="discount" class="form-control" value="{{ old('discount') ?? 0 }}" min="0" max="100" step="0.01">
                            @error('discount')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Paid Amount -->
                        <div class="col-md-3">
                            <label for="paid_amount" class="form-label">Paid Amount</label>
                            <input type="number" name="paid_amount" id="paid_amount" class="form-control" value="{{ old('paid_amount') ?? 0 }}" min="0" step="0.01">
                            @error('paid_amount')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Pending Amount Preview -->
                        <div class="col-md-4">
                            <label for="pending_amount_preview" class="form-label">Pending Amount</label>
                            <input type="number" id="pending_amount_preview" class="form-control" value="0" step="0.01" readonly>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-4">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select">
                                <option value="">Select Payment Method</option>
                                <option value="0" {{ old('payment_method')=='0' ? 'selected' : '' }}>Cash</option>
                                @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}" {{ old('payment_method')==$bank->id ? 'selected' : '' }}>
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
        $(function () {
            const externalOptionValue = '__external__';
            const routeSearchReferrers = "{{ route('consultations.search-referrers') }}";
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
            const consultationForm = document.querySelector('form[action="{{ route('consultations.store') }}"]');

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

            $('#patient_id, #doctor_id, #payment_method').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

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

            consultationForm.addEventListener('submit', function () {
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

            const recalcPendingAmount = () => {
                const fee = parseFloat($('#fee').val()) || 0;
                const discount = parseFloat($('#discount').val()) || 0;
                const paidAmount = parseFloat($('#paid_amount').val()) || 0;
                const discountAmount = fee * (discount / 100);
                const maxPayable = Math.max(0, fee - discountAmount);
                const pendingAmount = Math.max(0, maxPayable - paidAmount);
                $('#pending_amount_preview').val(pendingAmount.toFixed(2));
            };

            // Fetch fee when patient changes
            $('#patient_id').on('change', function () {
                const patientId = $(this).val();
                if (patientId) {
                    $.get('/patients/' + patientId + '/checkup-fee', function (data) {
                        $('#fee').val(data.fee);
                        recalcPendingAmount();
                    });
                } else {
                    $('#fee').val(0);
                    recalcPendingAmount();
                }
            });

            $('#fee, #discount, #paid_amount').on('input change', recalcPendingAmount);

            consultationForm.addEventListener('submit', function (event) {
                const fee = parseFloat($('#fee').val()) || 0;
                const discount = parseFloat($('#discount').val()) || 0;
                const paidAmount = parseFloat($('#paid_amount').val()) || 0;
                const discountAmount = fee * (discount / 100);
                const maxPayable = Math.max(0, fee - discountAmount);

                if (discount > 100) {
                    event.preventDefault();
                    alert('Discount cannot exceed 100%.');
                    return;
                }

                if (paidAmount > maxPayable) {
                    event.preventDefault();
                    alert('Paid Amount cannot exceed Total after Discount.');
                }
            });

            // Trigger change on page load to auto-load fee if patient_id is in query
            $('#patient_id').trigger('change');
            recalcPendingAmount();
        });
    </script>
@endpush
