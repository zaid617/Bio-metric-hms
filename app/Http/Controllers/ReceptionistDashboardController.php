<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Checkup;
use App\Models\TreatmentSession;
use App\Models\Transaction;
use Carbon\Carbon;

class ReceptionistDashboardController extends Controller
{
    public function index()
    {
        // 🔹 Logged-in receptionist ka branch
        $branch_id = auth()->user()->branch_id;

        $branch = auth()->user()->branch?->name ?? 'N/A';


        // 🔹 Today ka date (timezone-safe)
        $today = now()->toDateString(); // "2025-11-08"

        // ─────────── Today Appointments / Checkups ───────────
        $todayAppointmentsQuery = Checkup::where('branch_id', $branch_id)
            ->whereDate('created_at', $today);

        $todayAppointmentsCount = $todayAppointmentsQuery->count();
        $todayAppointmentsFee   = $todayAppointmentsQuery->sum('fee');

        // ─────────── Today Sessions ───────────
      $today = \Carbon\Carbon::today();

$todaySessionsQuery = TreatmentSession::where('branch_id', $branch_id)
    ->where('status', 2) // sirf wo sessions jinka status = 1 hai
    ->whereDate('created_at', $today); // aaj ki date ka filter

$todaySessionsCount = $todaySessionsQuery->count();
$todaySessionsFee   = $todaySessionsQuery->sum('session_fee');


        // ─────────── Satisfactory Sessions (Pending / Completed) ───────────
        $todayPendingSatisfactorySessions = TreatmentSession::where('branch_id', $branch_id)
            ->where('con_status', 0)
            ->count();

        $todayCompletedSatisfactorySessions = TreatmentSession::where('branch_id', $branch_id)
            ->where('con_status', 1)
            ->count();

        // ─────────── Enrollment Pending / Completed ───────────
        $enrollmentPending = TreatmentSession::where('branch_id', $branch_id)
            ->where('enrollment_status', 0)
            ->count();

        $enrollmentCompleted = TreatmentSession::where('branch_id', $branch_id)
            ->where('enrollment_status', 1)
            ->count();

        // ─────────── Pending Invoices ───────────
        $pendingInvoicesQuery = TreatmentSession::where('branch_id', $branch_id)
            ->where('payment_status', 'unpaid');

        $pendingInvoicesCount = $pendingInvoicesQuery->count();
        $pendingInvoicesTotal = $pendingInvoicesQuery->sum('session_fee');

        // ─────────── Today Payments Received ───────────
        $todayPayments = Transaction::where('branch_id', $branch_id)
            ->whereDate('created_at', $today) // timezone-safe
            ->where('type', '+')
            ->sum('amount');

        // ─────────── Return to Blade ───────────
        return view('receptionist.dashboard', compact(
            'todayAppointmentsCount',
            'todayAppointmentsFee',
            'todaySessionsCount',
            'todaySessionsFee',
            'todayPendingSatisfactorySessions',
            'todayCompletedSatisfactorySessions',
            'enrollmentPending',
            'enrollmentCompleted',
            'pendingInvoicesCount',
            'pendingInvoicesTotal',
            'todayPayments',
            'today',
            'branch'
        ));
    }
}
