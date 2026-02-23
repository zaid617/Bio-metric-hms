@extends('layouts.app')
@section('title')
    Invoice
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

    /* Hide everything */
    body * {
        visibility: hidden;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Show only invoice container */
    .invoice-container,
    .invoice-container * {
        visibility: visible;
        margin: 0 !important;
        padding: 0 !important;
    }

    .invoice-container {
        position: fixed !important;
        top: 0mm !important;
        left: 0.5mm !important;
        right: 0.5mm !important;
        width: 100% !important;
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
    }

    /* Remove all padding and margins from inner elements */
    .card {
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
        height: 100% !important;
    }

    .card-body {
        margin: 0 !important;
        padding: 3mm !important;
    }

    .invoice-header {
        margin-top: 0 !important;
        padding-top: 2mm !important;
    }

    /* Hide non-print elements */
    .no-print,
    .card-header,
    .page-title,
    x-page-title,
    [class*="page-title"],
    [class*="breadcrumb"] {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}
    /* Screen styles */
    .invoice-container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            min-height: 210mm;
        }
        .invoice-header {
            background-color: #f8f9fa;
            padding: 5px;
            border-radius: 5px;
            margin-bottom: 5px;
            text-align: center;
            border-bottom: 2px solid #fe0505;
        }
        .logo-title-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
            gap: 5px;
        }
        /* Logo image styling - Fixed size */
        .logo-img {
            width: 80px !important;
            height: 80px !important;
            object-fit: contain;
        }
        .title-container {
            text-align: center;
        }
        .center-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            line-height: 1.1;
            margin-bottom: 2px;
        }
        .center-tagline {
            font-size: 16px;
            color: #666;
            font-style: italic;
        }
        .center-fullname {
            font-size: 16px;
            font-weight: 600;
            color: #444;
            margin-top: 5px;
        }
        .patient-info {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .info-row {
            display: flex;
            margin-bottom: 6px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }
        .table-invoice {
            font-size: 14px;
        }
        .table-invoice th {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            font-weight: 600;
            padding: 8px;
        }
        .table-invoice td {
            padding: 8px;
        }
        .total-section {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 12px;
            margin-top: 15px;
        }
        .footer-section {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 10px;
        }
        .branches-section {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 5px;
            margin-bottom: 10px;
        }
        .branch-card {
            background: white;
            padding: 5px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            font-size: 12px;
        }
        .branch-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px dashed #e0e0e0;
            font-size: 13px;
        }
        .branch-info {
            line-height: 1.3;
        }
        .branch-info i {
            width: 16px;
            color: #6c757d;
        }
        .notes-section {
            background-color: #f0f8ff;
            padding: 8px 12px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 13px;
        }
        .notes-section p {
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .logo-title-container {
                flex-direction: column;
                text-align: center;
            }
            .title-container {
                text-align: center;
            }
            .branches-section {
                 grid-template-columns: 2fr;
            }
        }
    </style>
@section('content')
    <x-page-title title="Apps" subtitle="Invoice" />


    <div class="invoice-container">
        <div class="card radius-10">
            <div class="card-header py-2 no-print">
                <div class="row align-items-center g-2">
                    <div class="col-12 col-lg-6">
                        <h5 class="mb-0">BODYEXPERTS</h5>
                    </div>
                    <div class="col-12 col-lg-6 text-md-end">
                        <a href="javascript:;" id="exportPdfBtn" class="btn btn-danger btn-sm me-2">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Export as PDF
                        </a>
                        <a href="javascript:;" onclick="window.print()" class="btn btn-dark btn-sm"><i
                                class="bi bi-printer-fill me-2"></i>Print</a>
                    </div>
                </div>
            </div>

            <div class="printpdf">
            <!-- Invoice Header -->
            <div class="card-body pt-3">
                <div class="invoice-header">
                    <!-- First line: Logo with BODYEXPERTS and tagline -->
                    <div class="logo-title-container">
                        <!-- Your logo with proper sizing -->
                        <img src="{{ URL::asset('build/images/bodylogo.png') }}" class="logo-img" alt="BodyExperts Logo">
                        <div class="title-container">
                            <div class="center-name">BODYEXPERTS</div>
                            <div class="center-tagline">DEAR PAIN LET'S BREAK UP</div>
                        </div>
                    </div>

                    <!-- Second line: Center full name -->
                    <div class="center-fullname">
                        ORTHO-NEURO-SPORTS PHYSIOTHERAPY AND REHABILITATION CENTER
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="patient-info">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-row">
                                <span class="info-label">Name:</span>
                                <span>{{ $checkup->patient_name ?? 'N/A' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Date:</span>
                                <span>{{ format_date($checkup->created_at ?? 'N/A') }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">MR#:</span>
                                <span>{{ $checkup->patient_mr ?? 'N/A' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Invoice#:</span>
                                <span>{{ $checkup->id ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                          
                            <div class="info-row">
                                <span class="info-label">Age/Gender:</span>
                                <span>{{ $checkup->patient_age ?? 'N/A' }}y / {{ $checkup->gender ?? 'N/A' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Payment Mode:</span>
                                <span>{{ bank_get_name($checkup->payment_method) ?? 'N/A' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Printed By:</span>
                                <span>{{ auth()->user()->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Details -->
                <div class="table-responsive">
                    <table class="table table-invoice table-sm">
                        <thead>
                            <tr>
                                <th>SERVICE DESCRIPTION</th>
                                <th class="text-center" style="width: 20%;">AMOUNT (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Dr {{ $checkup->doctor_name ?? 'N/A' }} Consultation Charges</td>
                                <td class="text-center">{{ number_format($checkup->fee ?? 'N/A' )}}</td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                <!-- Total Section -->
                <div class="total-section">
                    <div class="row justify-content-end">
                        <div class="col-md-6">
                            <div class="row mb-2">
                                <div class="col-6 text-end">
                                    <strong>Subtotal:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    Rs. {{ $checkup->fee ?? 'N/A' }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6 text-end">
                                    <strong>Tax (%):</strong>
                                </div>
                                <div class="col-6 text-end">
                                    Rs. 0.00
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6 text-end">
                                    <strong>Discount:</strong>
                                </div>
                                <div class="col-6 text-end">
                                   {{ number_format( $checkup->fee -$checkup->paid_amount ?? 'N/A' )}}
                                </div>
                            </div>
                            <div class="row pt-2 border-top">
                                <div class="col-6 text-end">
                                    <h5 class="mb-0"><strong>Total:</strong></h5>
                                </div>
                                <div class="col-6 text-end">
                                    <h5 class="mb-0">Rs. {{ number_format($checkup->paid_amount ?? 'N/A' )}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="notes-section">
                    <p><strong>Notes:</strong> Payment due upon receipt. Bring invoice for next appointment. Contact for queries. Reschedule with 24h notice.</p>
                </div>
            </div>

            <!-- Footer with Branches -->
            <div class="card-footer footer-section">
                <div class="branches-section">
                    @foreach($branches as $branch)
                        <div class="branch-card">
                            <div class="branch-title">{{ $branch->name }}</div>
                            <div class="branch-info">
                                <div><i class="bi bi-geo-alt"></i> {{ $branch->city }}</div>
                                <div><i class="bi bi-house"></i> {{ $branch->address }}</div>
                                <div><i class="bi bi-telephone"></i> {{ $branch->phone }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="text-center mt-2">
                    <p class="mb-1">
                        <strong>THANK YOU FOR CHOOSING BODYEXPERTS</strong>
                    </p>
                    <p class="mb-0 text-muted">
                        <small>Printed On: {{ now()->format('d-m-Y h:i A') }}</small>
                    </p>
                </div>
            </div>
            </div>

        </div>
    </div>

@endsection
@push('script')
    <!--plugins-->
    <script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/main.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // PDF Export functionality - more specific selector
    const pdfButton = document.querySelector('.btn-danger.btn-sm.me-2');

    if (pdfButton) {
        pdfButton.addEventListener('click', function() {
            const element = document.querySelector('.printpdf');
            const options = {
                margin: [5, 5, 5, 5],
                filename: 'BodyExperts-Invoice-101714.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    logging: false
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };

            // Show loading indicator
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Generating PDF...';
            this.style.pointerEvents = 'none';

            html2pdf().set(options).from(element).save().finally(() => {
                // Restore button text after 2 seconds
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.pointerEvents = 'auto';
                }, 2000);
            });
        });
    } else {
        console.error('PDF button not found!');
    }
});
</script>
@endpush

