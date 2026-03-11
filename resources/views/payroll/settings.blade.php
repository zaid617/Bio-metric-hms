@extends('layouts.app')

@section('title') Payroll Settings @endsection

@push('css')
<style>
    .section-card {
        border-left: 4px solid #0d6efd;
        margin-bottom: 1.5rem;
    }
    .section-card .card-header {
        background: transparent;
        font-weight: 600;
        font-size: 0.95rem;
        letter-spacing: .3px;
    }
    .form-label { font-weight: 500; }
    .input-hint  { font-size: 0.78rem; color: #6c757d; margin-top: 2px; }
</style>
@endpush

@section('content')

<x-page-title title="Payroll" subtitle="Settings" />

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('payroll.settings.update') }}" method="POST">
    @csrf
    @method('PUT')

    {{-- ── Shift & Schedule ────────────────────────────────────────────────── --}}
    <div class="card section-card">
        <div class="card-header">
            <i class="material-icons-outlined align-middle me-1" style="font-size:1.1rem">schedule</i>
            Shift &amp; Schedule
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Default Shift Hours</label>
                    <input type="number" step="0.5" min="1" max="24"
                           name="default_shift_hours"
                           value="{{ old('default_shift_hours', $settings->default_shift_hours) }}"
                           class="form-control @error('default_shift_hours') is-invalid @enderror">
                    <div class="input-hint">Standard hours per working day</div>
                    @error('default_shift_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Shift Start Time</label>
                    <input type="time"
                           name="shift_start"
                           value="{{ old('shift_start', $settings->shift_start) }}"
                           class="form-control @error('shift_start') is-invalid @enderror">
                    <div class="input-hint">Official shift start (HH:MM)</div>
                    @error('shift_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Late Grace Period (minutes)</label>
                    <input type="number" min="0" max="120"
                           name="late_grace_minutes"
                           value="{{ old('late_grace_minutes', $settings->late_grace_minutes) }}"
                           class="form-control @error('late_grace_minutes') is-invalid @enderror">
                    <div class="input-hint">Minutes allowed before marking late</div>
                    @error('late_grace_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Overtime Multiplier</label>
                    <input type="number" step="0.1" min="1" max="5"
                           name="overtime_multiplier"
                           value="{{ old('overtime_multiplier', $settings->overtime_multiplier) }}"
                           class="form-control @error('overtime_multiplier') is-invalid @enderror">
                    <div class="input-hint">e.g. 1.5 = 1.5× normal rate</div>
                    @error('overtime_multiplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 d-flex align-items-center mt-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox"
                               id="work_on_saturday" name="work_on_saturday"
                               value="1" {{ old('work_on_saturday', $settings->work_on_saturday) ? 'checked' : '' }}>
                        <label class="form-check-label" for="work_on_saturday">
                            Work on Saturday
                        </label>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Commission Rates ────────────────────────────────────────────────── --}}
    <div class="card section-card" style="border-left-color: #198754;">
        <div class="card-header">
            <i class="material-icons-outlined align-middle me-1" style="font-size:1.1rem">percent</i>
            Commission Rates
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Treatment Extension Commission</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" max="1"
                               name="treatment_extension_commission"
                               value="{{ old('treatment_extension_commission', $settings->treatment_extension_commission) }}"
                               class="form-control @error('treatment_extension_commission') is-invalid @enderror">
                        <span class="input-group-text">rate</span>
                        @error('treatment_extension_commission')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="input-hint">e.g. 0.10 = 10%</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Assessment Incentive</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" max="1"
                               name="assessment_incentive"
                               value="{{ old('assessment_incentive', $settings->assessment_incentive) }}"
                               class="form-control @error('assessment_incentive') is-invalid @enderror">
                        <span class="input-group-text">rate</span>
                        @error('assessment_incentive')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="input-hint">e.g. 0.05 = 5%</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Personal Patient Commission</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" max="1"
                               name="personal_patient_commission"
                               value="{{ old('personal_patient_commission', $settings->personal_patient_commission) }}"
                               class="form-control @error('personal_patient_commission') is-invalid @enderror">
                        <span class="input-group-text">rate</span>
                        @error('personal_patient_commission')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="input-hint">e.g. 0.20 = 20%</div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Bonuses ─────────────────────────────────────────────────────────── --}}
    <div class="card section-card" style="border-left-color: #ffc107;">
        <div class="card-header">
            <i class="material-icons-outlined align-middle me-1" style="font-size:1.1rem">star</i>
            Bonuses
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Satisfactory Session Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">PKR</span>
                        <input type="number" step="1" min="0"
                               name="satisfactory_session_amount"
                               value="{{ old('satisfactory_session_amount', $settings->satisfactory_session_amount) }}"
                               class="form-control @error('satisfactory_session_amount') is-invalid @enderror">
                        @error('satisfactory_session_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Satisfaction Threshold (%)</label>
                    <div class="input-group">
                        <input type="number" min="0" max="100"
                               name="satisfaction_threshold"
                               value="{{ old('satisfaction_threshold', $settings->satisfaction_threshold) }}"
                               class="form-control @error('satisfaction_threshold') is-invalid @enderror">
                        <span class="input-group-text">%</span>
                        @error('satisfaction_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="input-hint">Min score to qualify for bonus</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Satisfaction Bonus / Feedback</label>
                    <div class="input-group">
                        <span class="input-group-text">PKR</span>
                        <input type="number" step="1" min="0"
                               name="satisfaction_bonus_per_feedback"
                               value="{{ old('satisfaction_bonus_per_feedback', $settings->satisfaction_bonus_per_feedback) }}"
                               class="form-control @error('satisfaction_bonus_per_feedback') is-invalid @enderror">
                        @error('satisfaction_bonus_per_feedback')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Reference Bonus / Patient</label>
                    <div class="input-group">
                        <span class="input-group-text">PKR</span>
                        <input type="number" step="1" min="0"
                               name="reference_bonus_per_patient"
                               value="{{ old('reference_bonus_per_patient', $settings->reference_bonus_per_patient) }}"
                               class="form-control @error('reference_bonus_per_patient') is-invalid @enderror">
                        @error('reference_bonus_per_patient')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Awards & Deductions ─────────────────────────────────────────────── --}}
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card section-card h-100" style="border-left-color: #6f42c1;">
                <div class="card-header">
                    <i class="material-icons-outlined align-middle me-1" style="font-size:1.1rem">emoji_events</i>
                    Awards
                </div>
                <div class="card-body">
                    <label class="form-label">Punctuality Award Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">PKR</span>
                        <input type="number" step="1" min="0"
                               name="punctuality_amount"
                               value="{{ old('punctuality_amount', $settings->punctuality_amount) }}"
                               class="form-control @error('punctuality_amount') is-invalid @enderror">
                        @error('punctuality_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="input-hint">Given for full punctuality in the period</div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card section-card h-100" style="border-left-color: #dc3545;">
                <div class="card-header">
                    <i class="material-icons-outlined align-middle me-1" style="font-size:1.1rem">remove_circle_outline</i>
                    Deductions
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Absent Deduction / Day</label>
                            <div class="input-group">
                                <span class="input-group-text">PKR</span>
                                <input type="number" step="1" min="0"
                                       name="absent_per_day"
                                       value="{{ old('absent_per_day', $settings->absent_per_day) }}"
                                       class="form-control @error('absent_per_day') is-invalid @enderror">
                                @error('absent_per_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Late Deduction / Day</label>
                            <div class="input-group">
                                <span class="input-group-text">PKR</span>
                                <input type="number" step="1" min="0"
                                       name="late_per_day"
                                       value="{{ old('late_per_day', $settings->late_per_day) }}"
                                       class="form-control @error('late_per_day') is-invalid @enderror">
                                @error('late_per_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 mb-5">
        <button type="submit" class="btn btn-primary px-5">
            <i class="material-icons-outlined align-middle me-1" style="font-size:1rem">save</i>
            Save Payroll Settings
        </button>
    </div>

</form>

@endsection

@push('script')
<script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
