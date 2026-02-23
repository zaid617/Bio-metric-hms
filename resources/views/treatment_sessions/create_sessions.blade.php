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
                                    <strong>Patient Name:</strong> {{ $patient->name ?? 'N/A' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light d-flex align-items-center">
                                <i class="material-icons-outlined text-success me-2">badge</i>
                                <div>
                                    <strong>MR No:</strong> {{ $patient->mr ?? 'N/A' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light d-flex align-items-center">
                                <i class="material-icons-outlined text-warning me-2">calendar_today</i>
                                <div>
                                    <strong>Date:</strong> {{ format_date($ongoingSessions->created_at ?? now()) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light d-flex align-items-center">
                                <i class="material-icons-outlined text-info me-2">local_hospital</i>
                                <div>
                                    <strong>DR Consultation:</strong> {{ doctor_get_name($ongoingSessions->doctor_id) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light d-flex align-items-center">
                                <i class="material-icons-outlined text-danger me-2">medical_information</i>
                                <div>
                                    <strong>Session DR:</strong> {{ doctor_get_name($ongoingSessions->ss_dr_id)}}
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 border rounded bg-light">
                                <i class="material-icons-outlined text-primary me-2">assignment_turned_in</i>
                                <strong>Diagnosis:</strong>
                                <p class="mt-2 mb-0">{{ $ongoingSessions->diagnosis ?? 'No diagnosis provided.' }}</p>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 border rounded bg-light">
                                <i class="material-icons-outlined text-secondary me-2">notes</i>
                                <strong>Note:</strong>
                                <p class="mt-2 mb-0">{{ $ongoingSessions->note ?? '-' }}</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>




            <!-- Enrollment Update Form -->
            <div class="card p-3">
                <h5>Enroll {{ $patient->name ?? 'the Patient' }} in Treatment Sessions</h5>

                <form method="POST" action="{{ route('treatment-sessions.enrollmentUpdate', $ongoingSessions->id ?? 0) }}">
                    @csrf
                    @method('PUT')

                    <!-- Hidden IDs -->
                    <input type="hidden" name="session_id" value="{{ $ongoingSessions->id ?? '' }}">
                    <input type="hidden" id="session_count_input" name="session_count" value="0">
                    <input type="hidden" id="dues_amount_input" name="dues_amount" value="0">

                    <!-- Sessions Table -->
                    <div class="mb-3">
                        <label class="form-label">Session Dates & Times</label>
                        <table id="sessionTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Session Date</th>
                                    <th>Session Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <p class="mt-2">Total Sessions: <span id="sessionCount">0</span></p>
                    </div>
                    <!-- Fees & Payments -->
                    <div class="row">
                        <!-- Session Fee -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total Session Fee</label>
                            <input type="number" class="form-control" name="session_fee" id="session_fee" min="0" required>
                        </div>

                        <!-- Paid Amount -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Paid Amount</label>
                            <input type="number" class="form-control" name="paid_amount" id="paid_amount" min="0" required>
                        </div>
                        <!-- Payment Method -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payment Method</label>
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

                    <!-- Fee Summary -->
                    <!-- Fee Summary -->
                    <div class="card mb-3">
                        <div class="card-body bg-light">
                            <h5 class="card-title mb-3">Payment Status</h5>
                            <div class="row text-center">
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="p-2 border rounded bg-white shadow-sm">
                                        <small class="text-muted">Total Fee</small>
                                        <h6 class="mb-0 text-primary" id="totalFee">0</h6>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="p-2 border rounded bg-white shadow-sm">
                                        <small class="text-muted">Per Session Fee</small>
                                        <h6 class="mb-0 text-success" id="perSessionFee">0</h6>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="p-2 border rounded bg-white shadow-sm">
                                        <small class="text-muted">Paid Amount</small>
                                        <h6 class="mb-0 text-info" id="paidAmount">0</h6>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="p-2 border rounded bg-white shadow-sm">
                                        <small class="text-muted">Due Amount</small>
                                        <h6 class="mb-0 text-danger" id="dueAmount">0</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="mb-3 text-end">
                        <button type="submit" class="btn btn-primary">Save Sessions</button>
                    </div>
                </form>
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
let sessionIndex = 0;

function formatDate(date) {
    const d = new Date(date);
    return d.getFullYear() + '-' +
           String(d.getMonth()+1).padStart(2,'0') + '-' +
           String(d.getDate()).padStart(2,'0');
}

function addRow(button=null){
    let newDate = new Date();
    const rows = document.querySelectorAll('#sessionTable tbody tr');
    if(rows.length > 0){
        const lastDateInput = rows[rows.length-1].querySelector('input[type="date"]');
        const lastDate = new Date(lastDateInput.value);
        newDate = new Date(lastDate);
        newDate.setDate(newDate.getDate()+1);
    }

    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="date" name="sessions[${sessionIndex}][date]" class="form-control" required value="${formatDate(newDate)}"></td>
        <td><input type="time" name="sessions[${sessionIndex}][time]" class="form-control" required value="12:00"></td>
        <td>
            <button type="button" class="btn btn-success btn-sm me-1" onclick="addRow(this)">➕</button>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">❌</button>
        </td>
    `;
    if(button){
        button.closest('tr').after(row);
    }else{
        document.querySelector('#sessionTable tbody').appendChild(row);
    }

    sessionIndex++;
    updateSessionCount();
    calculateFees();
}

function removeRow(button){
    button.closest('tr').remove();
    updateSessionCount();
    calculateFees();
}

function updateSessionCount(){
    const count = document.querySelectorAll('#sessionTable tbody tr').length;
    document.getElementById('sessionCount').innerText = count;
    document.getElementById('session_count_input').value = count; // ✅ hidden update
}

function calculateFees(){
    const sessionCount = document.querySelectorAll('#sessionTable tbody tr').length;
    const sessionFee = parseFloat(document.getElementById('session_fee').value) || 0;
    const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;

    const perSession = sessionCount > 0 ? (sessionFee/sessionCount).toFixed(2) : 0;
    const due = (sessionFee - paidAmount).toFixed(2);

    document.getElementById('totalFee').innerText = sessionFee.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('perSessionFee').innerText = perSession.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('paidAmount').innerText = paidAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('dueAmount').innerText = due.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('dues_amount_input').value = due;
}

document.addEventListener('DOMContentLoaded', function(){
    addRow(); // add default first row
    document.getElementById('session_fee').addEventListener('input', calculateFees);
    document.getElementById('paid_amount').addEventListener('input', calculateFees);
});
</script>
@endpush
