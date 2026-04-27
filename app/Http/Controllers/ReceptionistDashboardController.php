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
        $user = auth()->user();

        // 🔹 Logged-in receptionist ka branch
        $branch_id = $user->branch_id;

        $branch = $user->branch?->name ?? 'N/A';


        // 🔹 Today ka date (timezone-safe)
        $today = Carbon::today();

        // ─────────── Today Appointments / Checkups ───────────
        $todayAppointmentsQuery = Checkup::where('branch_id', $branch_id)
            ->whereDate('created_at', $today);

        $todayAppointmentsCount = $todayAppointmentsQuery->count();
        $todayAppointmentsFee   = $todayAppointmentsQuery->sum('fee');
        $todayAppointmentsPending = (clone $todayAppointmentsQuery)
            ->selectRaw('SUM(CASE WHEN pending_amount IS NOT NULL THEN pending_amount ELSE CASE WHEN (COALESCE(fee,0)-(COALESCE(fee,0)*(COALESCE(discount,0)/100))-COALESCE(paid_amount,0)) > 0 THEN (COALESCE(fee,0)-(COALESCE(fee,0)*(COALESCE(discount,0)/100))-COALESCE(paid_amount,0)) ELSE 0 END END) as total_pending')
            ->value('total_pending') ?? 0;

        // ─────────── Today Sessions ───────────
        $todaySessionsQuery = TreatmentSession::where('branch_id', $branch_id)
            ->where('status', 2)
            ->whereDate('created_at', $today);

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

        // ─────────── Receptionist Earnings (Own Entries Only) ───────────
        $receptionistEarningsQuery = Transaction::where('branch_id', $branch_id)
            ->where('entry_by', $user->id)
            ->where('type', '+');

        $totalReceptionistEarning = (clone $receptionistEarningsQuery)->sum('amount');
        $todayReceptionistEarning = (clone $receptionistEarningsQuery)
            ->whereDate('created_at', $today)
            ->sum('amount');

        // ─────────── Return to Blade ───────────
        return view('receptionist.dashboard', compact(
            'todayAppointmentsCount',
            'todayAppointmentsFee',
            'todayAppointmentsPending',
            'todaySessionsCount',
            'todaySessionsFee',
            'todayPendingSatisfactorySessions',
            'todayCompletedSatisfactorySessions',
            'enrollmentPending',
            'enrollmentCompleted',
            'pendingInvoicesCount',
            'pendingInvoicesTotal',
            'todayPayments',
            'todayReceptionistEarning',
            'totalReceptionistEarning',
            'today',
            'branch'
        ));
    }
}
