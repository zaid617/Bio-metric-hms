@extends('layouts.app')
@section('title')
    Checkup Details
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/input-tags/css/tagsinput.css') }}" rel="stylesheet">
@endpush

@section('content')
    <x-page-title title="Checkup" subtitle="Details" />

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                <div class="card-body">

                    <!-- Patient Name -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Patient:</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->patient_name ?? 'N/A' }}" readonly>
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Date:</strong></label>
                        <input type="text" class="form-control"
                               value="{{ \Carbon\Carbon::parse($checkup->created_at)->format('d-m-Y') }}" readonly>
                    </div>

                    <!-- Phone -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Phone:</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->patient_phone ?? 'N/A' }}" readonly>
                    </div>

                    <!-- Doctor -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Doctor:</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->doctor_name ?? 'N/A' }}" readonly>
                    </div>

                    <!-- Branch -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Branch:</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->branch_name ?? 'N/A' }}" readonly>
                    </div>

                    <!-- Fee -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Consultation Fee (Rs):</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->fee ?? 0 }}" readonly>
                    </div>

                    <!-- Paid Amount -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Paid Amount (Rs):</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->paid_amount ?? 0 }}" readonly>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Payment Method:</strong></label>
                        <input type="text" class="form-control" value="{{ $checkup->payment_method ?? 'N/A' }}" readonly>
                    </div>

                    <!-- Checkup Status -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Status:</strong></label>
                        @php $status = (int)($checkup->checkup_status ?? 0); @endphp
                        @if($status === 0)
                            <span class="badge bg-warning text-dark">Pending</span>
                        @elseif($status === 1)
                            <span class="badge bg-success">Completed</span>
                        @elseif($status === 2)
                            <span class="badge bg-danger">Cancelled</span>
                        @else
                            <span class="badge bg-secondary">Unknown</span>
                        @endif
                    </div>

                    <a href="/checkups" class="btn btn-secondary mt-3">Back to List</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <!--plugins-->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/input-tags/js/tagsinput.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
