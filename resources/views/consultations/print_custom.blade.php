@extends('layouts.app')

@section('title')
    Invoice / Checkup Refund
@endsection

<style>
    @media print {
        @page {
            margin: 0.5mm !important;
            size: A5;
        }
        body, html {
            margin: 0 !important;
            padding: 0 !important;
            height: 100% !important;
        }
        body * {
            visibility: hidden;
        }
        .invoice-container, .invoice-container * {
            visibility: visible;
        }
        .invoice-container {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100%;
            height: 100%;
            box-shadow: none !important;
            border: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .card-body {
            padding: 3mm !important;
        }
    }

    .invoice-container {
        max-width: 100%;
        margin: 0 auto;
        background: white;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .invoice-header {
        background-color: #f8f9fa;
        padding: 5px;
        border-radius: 5px;
        margin-bottom: 5px;
        text-align: center;
        border-bottom: 2px solid #fe0505;
    }
    .logo-img {
        width: 80px;
        height: 80px;
        object-fit: contain;
    }
    .center-name { font-size: 24px; font-weight: bold; color: #333; }
    .center-tagline { font-size: 16px; color: #666; font-style: italic; }
    .center-fullname { font-size: 16px; font-weight: 600; color: #444; margin-top: 5px; }
    .patient-info { background-color: #f8f9fa; padding: 12px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; }
    .info-row { display: flex; margin-bottom: 6px; }
    .info-label { font-weight: bold; width: 120px; color: #555; }
</style>

@section('content')
@php
    $checkup = $checkup ?? null;
    $banks = $banks ?? \App\Models\Bank::all();
@endphp

<div class="invoice-container">
    <div class="card radius-10">
        <div class="card-body pt-3">

            <!-- HEADER -->
            <div class="invoice-header">
                <img src="{{ URL::asset('build/images/bodylogo.png') }}" class="logo-img" alt="Logo">
                <div>
                    <div class="center-name">BODYEXPERTS</div>
                    <div class="center-tagline">DEAR PAIN LET'S BREAK UP</div>
                </div>
                <div class="center-fullname">
                    ORTHO-NEURO-SPORTS PHYSIOTHERAPY AND REHABILITATION CENTER
                </div>
            </div>

            <!-- PATIENT INFO -->
            <div class="patient-info">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row"><span class="info-label">Name:</span> <span>{{ $checkup->patient_name ?? '-' }}</span></div>
                        <div class="info-row"><span class="info-label">Date:</span> <span>{{ \Carbon\Carbon::parse($checkup->created_at)->format('d-m-Y') }}</span></div>
                        <div class="info-row"><span class="info-label">MR#:</span> <span>{{ $checkup->mr ?? '-' }}</span></div>
                        <div class="info-row"><span class="info-label">Invoice#:</span> <span>{{ $checkup->id }}</span></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row"><span class="info-label">Phone:</span> <span>{{ $checkup->patient_phone ?? '-' }}</span></div>
                        <div class="info-row"><span class="info-label">Age/Gender:</span> <span>{{ $checkup->age ?? '-' }}y / {{ $checkup->gender ?? '-' }}</span></div>
                        <div class="info-row"><span class="info-label">Payment Mode:</span> <span>{{ bank_get_name($checkup->payment_method) ?? 'Cash' }}</span></div>
                        <div class="info-row"><span class="info-label">Printed By:</span> <span>{{ auth()->user()->name ?? '-' }}</span></div>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>SERVICE DESCRIPTION</th>
                            <th class="text-center">AMOUNT (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Dr {{ $checkup->doctor_name ?? '-' }} Consultation Charges</td>
                            <td class="text-center">{{ number_format($checkup->fee ?? 0,2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TOTALS -->
            <div class="total-section mt-3 p-3 bg-light rounded">
                <div class="row justify-content-end">
                    <div class="col-md-6">
                        <div class="row mb-2">
                            <div class="col-6 text-end"><strong>Total Paid:</strong></div>
                            <div class="col-6 text-end">Rs. {{ number_format($checkup->paid_amount ?? 0,2) }}</div>
                        </div>
                        <div class="row border-top pt-2">
                            <div class="col-6 text-end"><h5><strong>Total Fee:</strong></h5></div>
                            <div class="col-6 text-end"><h5>Rs. {{ number_format($checkup->fee ?? 0,2) }}</h5></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHECKUP REFUND FORM -->
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="mb-3 text-danger fw-bold">💸 Process Checkup Refund</h5>

                    <form action="{{ route('checkups.refund') }}" method="POST">
                        @csrf
                        <input type="hidden" name="checkup_id" value="{{ $checkup->id }}">

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Refund Method</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="">Select Refund Method</option>
                                    <option value="0">Cash</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}">
                                            {{ $bank->bank_name }} ({{ $bank->account_no }}) - {{ $bank->account_title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Refund Amount (Rs.)</label>
                                <input type="number" name="amount" class="form-control"
                                       step="1"
                                       min="1"
                                       placeholder="Enter refund amount"
                                       required>
                                <small class="text-muted">Enter any positive amount</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Remarks</label>
                                <input type="text" name="remark" class="form-control" placeholder="Optional note">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-danger">
                                💰 Process Refund
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
