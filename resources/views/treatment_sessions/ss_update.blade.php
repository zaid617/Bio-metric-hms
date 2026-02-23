@extends('layouts.app')

@section('title', 'Treatment Slip & Sessions')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-12">
        <div class="card">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="material-icons-outlined me-2">assignment</i>
                    <h5 class="mb-0 text-white">Treatment Session Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light d-flex align-items-center">
                                <i class="material-icons-outlined text-primary me-2">person</i>
                                <div>
                                    <strong>Patient Name:</strong> {{ patient_get_name($session->patient_id) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light d-flex align-items-center">
                                <i class="material-icons-outlined text-success me-2">badge</i>
                                <div>
                                    <strong>MR No:</strong> {{ patient_get_mr($session->patient_id) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light d-flex align-items-center">
                                <i class="material-icons-outlined text-warning me-2">calendar_today</i>
                                <div>
                                    <strong>Date:</strong> {{ format_date($session->created_at ?? now()) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light d-flex align-items-center">
                                <i class="material-icons-outlined text-info me-2">local_hospital</i>
                                <div>
                                    <strong>DR Consultation:</strong> {{ doctor_get_name($session->doctor_id) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light d-flex align-items-center">
                                <i class="material-icons-outlined text-danger me-2">medical_information</i>
                                <div>
                                    <strong>Session DR:</strong> {{ doctor_get_name($session->ss_dr_id) }}
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('doctor-consultations.update-status') }}">
                            @csrf
                            <input type="hidden" name="session_id" value="{{ $session->id }}">
                            <div class="col-12">
                                <div class="p-3 border rounded bg-light">
                                    <i class="material-icons-outlined text-primary me-2">assignment_turned_in</i>
                                    <strong>Diagnosis:</strong>
                                    <textarea class="form-control" rows="3" name="diagnosis">{{ $session->diagnosis ?? 'No diagnosis provided.' }}</textarea>
                                    {{-- <p class="mt-2 mb-0">{{ $session->diagnosis ?? 'No diagnosis provided.' }}</p> --}}
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 border rounded bg-light">
                                    <i class="material-icons-outlined text-secondary me-2">notes</i>
                                    <strong>Note:</strong>
                                    <textarea class="form-control" rows="3" name="note">{{ $session->note ?? 'No additional notes.' }}</textarea>
                                    {{-- <p class="mt-2 mb-0">{{ $session->note ?? '-' }}</p> --}}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="con_status" class="form-label">Session Status</label>
                                <select name="con_status" id="con_status" class="form-select">
                                    <option value="0" {{ ($session->con_status ?? 0) == 0 ? 'selected' : '' }}>Pending</option>
                                    <option value="1" {{ ($session->con_status ?? 0) == 1 ? 'selected' : '' }}>Completed</option>

                                </select>
                            </div>

                            <!-- Submit -->
                            <div class="mb-4 text-end">
                                <button type="submit" class="btn btn-primary">Update Status</button>
                            </div>

                        </form>


                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection

@push('script')
 {{-- Core Plugins --}}
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
<script>
@endpush
