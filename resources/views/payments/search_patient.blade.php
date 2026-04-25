@extends('layouts.app')

@section('title', 'Payment Return')

@push('css')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    .table-responsive { overflow: visible; }
</style>
@endpush

@section('content')
    <x-page-title title="Payment Return" subtitle="Search patients and manage payment refunds" />

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3">Returned Payments</h6>
            <form method="GET" class="row g-2 align-items-end">
                @if(!empty($isSuperAdmin))
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-select">
                            <option value="">All Branches</option>
                            @foreach(($branches ?? collect()) as $branch)
                                <option value="{{ $branch->id }}" {{ (string)($selectedBranchId ?? '') === (string)$branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Patient, MR, invoice, transaction ID">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            @if(!empty($isSuperAdmin))
                                <th>Branch</th>
                            @endif
                            <th>Patient</th>
                            <th>MR</th>
                            <th>Invoice</th>
                            <th>Method</th>
                            <th>Remark</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($returnedPayments ?? collect()) as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>{{ format_datetime($payment->created_at) }}</td>
                                @if(!empty($isSuperAdmin))
                                    <td>{{ $payment->branch_name ?? 'N/A' }}</td>
                                @endif
                                <td>{{ $payment->patient_name ?? 'N/A' }}</td>
                                <td>{{ $payment->patient_mr ?? 'N/A' }}</td>
                                <td>#{{ $payment->invoice_id ?? 'N/A' }}</td>
                                <td>{{ $payment->bank_name ?? 'Cash' }}</td>
                                <td>{{ $payment->Remx ?? '-' }}</td>
                                <td class="text-end text-danger">{{ number_format((float) ($payment->amount ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ !empty($isSuperAdmin) ? 9 : 8 }}" class="text-center text-muted">No returned payments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8f9fa;">
                    <h5 class="mb-0 text-dark">Search Patient</h5>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <label for="patientSearch" class="form-label">Search Patient by Name / MR Number</label>
                        <input type="text" id="patientSearch" class="form-control" placeholder="Type to search..." autocomplete="off">
                    </div>

                    <div id="searchResults" class="table-responsive d-none">
                        <table id="patients-table" class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>MR No</th>
                                    <th>Patient Name</th>
                                    <th>Phone</th>
                                    <th>Age</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Dynamic rows load via AJAX --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Show Payments for Selected Patient --}}
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title" id="paymentModalLabel">Patient Payments</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <div id="paymentsTableContainer" class="table-responsive"></div>
          </div>
        </div>
      </div>
    </div>
@endsection

@push('script')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // 🧠 Step 1: Search as you type
    $('#patientSearch').on('keyup', function() {
        let query = $(this).val().trim();
        if (query.length < 2) {
            $('#searchResults').addClass('d-none');
            return;
        }

        $.ajax({
            url: "{{ route('payments.search-patient') }}",
            method: "GET",
            data: { q: query },
            success: function(response) {
                let tableBody = '';
                if (response.data.length > 0) {
                    $('#searchResults').removeClass('d-none');
                    response.data.forEach(p => {
                        tableBody += `
                            <tr>
                                <td>${p.mr}</td>
                                <td>${p.name}</td>
                                <td>${p.phone ?? '-'}</td>
                                <td>${p.age ?? '-'}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary viewPayments"
                                            data-id="${p.id}"
                                            data-name="${p.name}">
                                        View Payments
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tableBody = '<tr><td colspan="5" class="text-center text-muted">No patients found</td></tr>';
                }
                $('#patients-table tbody').html(tableBody);
            }
        });
    });

    // 🧠 Step 2: Show payments when user clicks “View Payments”
    $(document).on('click', '.viewPayments', function() {
        let patientId = $(this).data('id');
        let patientName = $(this).data('name');
        $('#paymentModalLabel').text(`Payments for ${patientName}`);

        $.ajax({
            url: "{{ route('payments.fetch-patient-payments') }}",
            method: "GET",
            data: { id: patientId },
            success: function(response) {
                $('#paymentsTableContainer').html(response.html);
                $('#paymentModal').modal('show');
            }
        });
    });
});
</script>
@endpush
