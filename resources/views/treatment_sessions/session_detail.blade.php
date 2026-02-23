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
                                        <strong>Patient Name:</strong> {{ $session->patient->name ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light d-flex align-items-center">
                                    <i class="material-icons-outlined text-success me-2">badge</i>
                                    <div>
                                        <strong>MR No:</strong> {{ $session->patient->mr ?? 'N/A' }}
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

                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light d-flex align-items-center">
                                    <i class="material-icons-outlined text-info me-2">local_hospital</i>
                                    <div>
                                        <strong>DR Consultation:</strong> {{ doctor_get_name($session->doctor_id) }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light d-flex align-items-center">
                                    <i class="material-icons-outlined text-danger me-2">medical_information</i>
                                    <div>
                                        <strong>Session DR:</strong> {{ doctor_get_name($session->ss_dr_id) }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light d-flex align-items-center">
                                    <i class="material-icons-outlined text-secondary me-2">copy</i>
                                    <div>
                                        <strong>Invoice:</strong>
                                        {{ $session->id }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 border rounded bg-light">
                                    <i class="material-icons-outlined text-primary me-2">assignment_turned_in</i>
                                    <strong>Diagnosis:</strong>
                                    <p class="mt-2 mb-0">{{ $session->diagnosis ?? 'No diagnosis provided.' }}</p>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 border rounded bg-light">
                                    <i class="material-icons-outlined text-secondary me-2">notes</i>
                                    <strong>Note:</strong>
                                    <p class="mt-2 mb-0">{{ $session->note ?? '-' }}</p>
                                </div>
                            </div>


                            <div class="col-12">
                                <div class="p-3 border rounded bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="material-icons-outlined text-secondary me-2">event</i>
                                            <h5 class="mb-0">Scheduled Sessions</h5>
                                        </div>
                                        <a href="#" class="btn btn-sm btn-primary">
                                            <i class="material-icons-outlined me-1 fs-6">edit</i> Sessions Update
                                        </a>
                                    </div>
                                    <hr>

                                    @if ($session->sessionTimes->isNotEmpty())
                                        <div class="row">
                                            @php
                                                $count = 1;
                                            @endphp
                                            @foreach ($session->sessionTimes->chunk(ceil($session->sessionTimes->count() / 4)) as $chunk)
                                                <div class="col-md-3">
                                                    @foreach ($chunk as $index => $time)
                                                        <div class="card shadow-sm mb-3 border-0">
                                                            <div class="card-body p-3">
                                                                <div
                                                                    class="d-flex justify-content-between align-items-center">
                                                                    <h6 class="mb-0 text-primary">{{ $count++ }}</h6>
                                                                    <span
                                                                        class="badge {{ $time->is_completed ? 'bg-success' : 'bg-warning text-dark' }}">
                                                                        {{ $time->is_completed ? 'Completed' : 'Pending' }}
                                                                    </span>
                                                                </div>
                                                                <p class="mb-1 text-muted small">
                                                                    <i
                                                                        class="material-icons-outlined fs-6 me-1">schedule</i>
                                                                    {{ format_date($time->session_datetime) }} at
                                                                    {{ format_time($time->session_datetime) }}
                                                                </p>
                                                                <p class="mb-2 small">
                                                                    <i class="material-icons-outlined fs-6 me-1">person</i>
                                                                    {{ doctor_get_name($time->completed_by_doctor_id) ?? 'N/A' }}
                                                                </p>

                                                                @if ($time->is_completed)
                                                                    <span class="text-success">
                                                                        <i class="material-icons-outlined fs-6 me-1">check_circle</i>
                                                                        Completed At {{ format_datetime($time->updated_at) ?? 'N/A' }}
                                                                    </span>

                                                                @else
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary rounded-pill"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#updateSessionModal"
                                                                        data-id="{{ $time->id }}"
                                                                        data-date="{{ format_date($time->session_datetime) }}"
                                                                        data-time="{{ format_time($time->session_datetime) }}"
                                                                        data-doctor_id="{{ $time->completed_by_doctor_id }}"
                                                                        data-status="{{ $time->is_completed ? 1 : 0 }}">
                                                                        <i class="material-icons-outlined me-1 fs-6">edit</i>
                                                                        Mark as Completed
                                                                    </button>

                                                                @endif

                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="mt-2 mb-0">No sessions scheduled.</p>
                                    @endif
                                </div>
                            </div>

                        </div>




                        <!-- Enrollment Update Form -->

                    </div>
                </div>
            </div>

            <!-- Update Session Modal -->
            <div class="modal fade" id="updateSessionModal" tabindex="-1" aria-labelledby="updateSessionModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content shadow-lg border-0 rounded-3">
                        <form method="POST" action="{{ route('sessions.mark-completed') }}">
                            @csrf

                            <!-- Modal Header -->
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title d-flex align-items-center text-white" id="updateSessionModalLabel">
                                    <i class="material-icons-outlined me-2 text-white">event</i> Update Session
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <!-- Modal Body -->
                            <div class="modal-body">
                                <input type="hidden" name="session_id" id="session_id">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="p-3 border rounded bg-light">
                                            <p class="mb-1"><strong>Date:</strong></p>
                                            <p class="text-muted mb-0" id="session_date">--</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 border rounded bg-light">
                                            <p class="mb-1"><strong>Time:</strong></p>
                                            <p class="text-muted mb-0" id="session_time">--</p>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <!-- Doctor -->
                                <div class="mb-3">
                                    <label for="session_doctor" class="form-label fw-bold">
                                        <i class="material-icons-outlined text-primary me-1">person</i> Doctor
                                    </label>
                                    <select name="doctor_id" id="session_doctor" class="form-select rounded-pill" required>
                                        <option value="">-- Select Doctor --</option>
                                        @foreach (get_doctors() as $doctor)
                                            <option value="{{ $doctor->id }}">
                                                {{ $doctor->first_name . ' ' . $doctor->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Status -->
                                <div class="mb-3">
                                    <label for="session_status" class="form-label fw-bold">
                                        <i class="material-icons-outlined text-primary me-1">flag</i> Status
                                    </label>
                                    <select name="status" id="session_status" class="form-select rounded-pill" required>
                                        <option value="1">Completed</option>
                                        <option value="0">Pending</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined me-1">close</i> Cancel
                                </button>
                                <button type="submit" class="btn btn-primary rounded-pill">
                                    <i class="material-icons-outlined me-1">save</i> Update Session
                                </button>
                            </div>
                        </form>
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
                var updateModal = document.getElementById('updateSessionModal');
                updateModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;

                    var id = button.getAttribute('data-id');
                    var date = button.getAttribute('data-date');
                    var time = button.getAttribute('data-time');
                    var doctorId = button.getAttribute('data-doctor_id');
                    var status = button.getAttribute('data-status');

                    // Debug logs
                    console.log("Modal Opened");
                    console.log("Doctor ID from button:", doctorId);

                    // Fill modal fields
                    document.getElementById('session_id').value = id;
                    document.getElementById('session_date').innerText = date;
                    document.getElementById('session_time').innerText = time;
                    document.getElementById('session_status').value = status;

                    // Doctor dropdown
                    let doctorDropdown = document.getElementById('session_doctor');

                    // Print all available doctor options
                    console.log("Doctor dropdown options:");
                    doctorDropdown.querySelectorAll('option').forEach(opt => {
                        console.log(opt.value, "-", opt.text);
                    });

                    // Try to select doctor
                    if (doctorId && doctorDropdown.querySelector('option[value="' + doctorId + '"]')) {
                        doctorDropdown.value = doctorId;
                        console.log("✅ Doctor selected:", doctorId);
                    } else {
                        doctorDropdown.value = "";
                        console.log("❌ Doctor not found in dropdown, defaulting to empty");
                    }
                });
            </script>
        @endpush
