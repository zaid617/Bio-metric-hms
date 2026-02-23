@extends('layouts.app')

@section('title', 'Receptionist Dashboard')

@push('css')
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<!-- Bootstrap 5 (agar layouts.app me include nahi hai) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    /* Dashboard Header */
    .dashboard-header {
        background-color: #f8f9fa;
        border-radius: 0.5rem;
        padding: 1rem 1.5rem;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .dashboard-header h5 {
        margin-bottom: 0;
        font-weight: 600;
    }
    .dashboard-header small {
        font-size: 0.85rem;
        color: #6c757d;
    }

    /* Dashboard Cards */
    .dashboard-card {
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        padding: 1rem;
        min-height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .dashboard-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .dashboard-icon {
        font-size: 2rem;
        margin-right: 1rem;
    }

    .dashboard-value {
        font-weight: 600;
        font-size: 1.25rem;
    }
    .dashboard-text {
        font-size: 0.85rem;
        color: #6c757d;
    }
    .dashboard-small-text {
        font-size: 0.75rem;
        color: #28a745;
    }

    /* Responsive */
    @media (max-width: 575px) {
        .dashboard-card {
            flex-direction: column;
            text-align: center;
        }
        .dashboard-icon {
            margin-bottom: 0.5rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container mt-3">

    <!-- Welcome Header (Separate Box) -->
    <div class="dashboard-header">
        <div>
            <h5>Welcome, {{ Auth::user()->name }}</h5>
            <small>Branch: {{ $branch }}</small>
        </div>
        <div>
            <small>{{ \Carbon\Carbon::now()->format('d M Y') }}</small>
        </div>
    </div>

    <!-- Dashboard Cards -->
    <div class="row g-3">

        <!-- Today Appointments -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="dashboard-card">
                <i class="fas fa-calendar-alt text-primary dashboard-icon"></i>
                <div>
                    <div class="dashboard-value">{{ $todayAppointmentsCount }}</div>
                    <div class="dashboard-text">Today Appointments</div>
                    <div class="dashboard-small-text">Fees: ₨{{ number_format($todayAppointmentsFee,2) }} 🇵🇰</div>
                </div>
            </div>
        </div>

        <!-- Pending Satisfactory Sessions -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="dashboard-card">
                <i class="fas fa-clock text-warning dashboard-icon"></i>
                <div>
                    <div class="dashboard-value">{{ $todayPendingSatisfactorySessions }}</div>
                    <div class="dashboard-text">Pending Satisfactory Sessions</div>
                </div>
            </div>
        </div>

        <!-- Completed Satisfactory Sessions -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="dashboard-card">
                <i class="fas fa-check text-success dashboard-icon"></i>
                <div>
                    <div class="dashboard-value">{{ $todayCompletedSatisfactorySessions }}</div>
                    <div class="dashboard-text">Completed Satisfactory Sessions</div>
                </div>
            </div>
        </div>

        <!-- Today Sessions -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="dashboard-card">
                <i class="fas fa-calendar-check text-info dashboard-icon"></i>
                <div>
                    <div class="dashboard-value">{{ $todaySessionsCount }}</div>
                    <div class="dashboard-text">Today Sessions</div>
                    <div class="dashboard-small-text">Fees: ₨{{ number_format($todaySessionsFee,2) }} 🇵🇰</div>
                </div>
            </div>
        </div>

        <!-- Enrollment Pending -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="dashboard-card">
                <i class="fas fa-user-clock text-danger dashboard-icon"></i>
                <div>
                    <div class="dashboard-value">{{ $enrollmentPending }}</div>
                    <div class="dashboard-text">Enrollment Pending</div>
                </div>
            </div>
        </div>

        <!-- Enrollment Completed -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="dashboard-card">
                <i class="fas fa-user-check text-success dashboard-icon"></i>
                <div>
                    <div class="dashboard-value">{{ $enrollmentCompleted }}</div>
                    <div class="dashboard-text">Enrollment Completed</div>
                </div>
            </div>
        </div>

        <!-- Pending Invoices -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="dashboard-card">
                <i class="fas fa-file-invoice-dollar text-secondary dashboard-icon"></i>
                <div>
                    <div class="dashboard-value">{{ $pendingInvoicesCount }}</div>
                    <div class="dashboard-text">Pending Invoices</div>
                    <div class="dashboard-small-text">Total: ₨{{ number_format($pendingInvoicesTotal,2) }} 🇵🇰</div>
                </div>
            </div>
        </div>

        <!-- Today Payments Received -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="dashboard-card">
                <i class="fas fa-money-bill-wave text-dark dashboard-icon"></i>
                <div>
                    <div class="dashboard-value">₨{{ number_format($todayPayments,2) }} 🇵🇰</div>
                    <div class="dashboard-text">Today Payments Received</div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('script')
<!-- Font Awesome JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<!-- Perfect Scrollbar (optional) -->
<script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>

<!-- MetisMenu -->
<script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>

<!-- Custom Main JS -->
<script src="{{ URL::asset('build/js/main.js') }}"></script>
@endpush
