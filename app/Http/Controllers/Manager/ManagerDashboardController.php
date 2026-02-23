<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Transaction;
use Carbon\Carbon;

class ManagerDashboardController extends Controller
{
    // Dashboard for Manager
    public function index()
    {
        // Manager ka branch id
        $branchId = auth()->user()->branch_id;

        // Branch fetch
        $branch = Branch::find($branchId);

        if (!$branch) {
            abort(404, 'Branch not found.');
        }

        // Transactions Today for this branch
        $transactionsToday = Transaction::where('branch_id', $branch->id)
            ->whereDate('created_at', Carbon::today());

        // ────────────── Basic Counts ──────────────
        $totalDoctors  = Doctor::where('branch_id', $branch->id)->count();
        $totalPatients = Patient::where('branch_id', $branch->id)->count();

        // ────────────── Sessions Today (payment_type = 2) ──────────────
        $totalSessionsToday = (clone $transactionsToday)->where('payment_type', 2)->count();
        $sessionPaymentsToday = (clone $transactionsToday)
            ->where('payment_type', 2)
            ->where('type', '+')
            ->sum('amount');

        // ────────────── Consultations Today (payment_type = 1) ──────────────
        $totalConsultationsToday = (clone $transactionsToday)->where('payment_type', 1)->count();
        $checkupPaymentsToday = (clone $transactionsToday)
            ->where('payment_type', 1)
            ->where('type', '+')
            ->sum('amount');

        // ────────────── Total Payments Today ──────────────
        $totalPaymentsToday = (clone $transactionsToday)
            ->where('type', '+')
            ->sum('amount');

        // ────────────── Total Payments All Time (Cash Only) ──────────────
$totalPaymentsAll = Transaction::where('branch_id', $branch->id)
    ->where('type', '+')              // Only incoming
    ->where('payment_method', 'Cash') // Only cash
    ->sum('amount');

        // ────────────── Branch Stats Array ──────────────
        $branchStats = [
            'branch_name'             => $branch->name,
            'totalDoctors'            => $totalDoctors,
            'totalPatients'           => $totalPatients,
            'totalConsultationsToday' => $totalConsultationsToday,
            'totalSessionsToday'      => $totalSessionsToday,
            'checkupPaymentsToday'    => $checkupPaymentsToday,
            'sessionPaymentsToday'    => $sessionPaymentsToday,
            'totalPaymentsToday'      => $totalPaymentsToday,
            'totalPaymentsAll'        => $totalPaymentsAll,
        ];

        return view('manager.dashboard', compact('branchStats'));
    }
}
