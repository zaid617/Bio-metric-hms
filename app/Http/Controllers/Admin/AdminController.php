<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Transaction;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $branches = Branch::all();

        $branchStats = $branches->map(function ($branch) {

            // ────────────── Basic Counts ──────────────
            $totalDoctors  = Doctor::where('branch_id', $branch->id)->count();
            $totalPatients = Patient::where('branch_id', $branch->id)->count();

            // ────────────── Transactions Today ──────────────
            $transactionsToday = Transaction::where('branch_id', $branch->id)
                ->whereDate('created_at', Carbon::today());

            // Sessions Today (payment_type = 2)
            $totalSessionsToday = (clone $transactionsToday)->where('payment_type', 2)->count();
            $sessionPaymentsToday = (clone $transactionsToday)
                ->where('payment_type', 2)
                ->where('type', '+')
                ->sum('amount');

            // Consultations Today (payment_type = 1)
            $totalConsultationsToday = (clone $transactionsToday)->where('payment_type', 1)->count();
            $checkupPaymentsToday = (clone $transactionsToday)
                ->where('payment_type', 1)
                ->where('type', '+')
                ->sum('amount');

            // ────────────── Total Payments (Today only) ──────────────
            $totalPaymentsToday = (clone $transactionsToday)
                ->where('type', '+')
                ->sum('amount');

            // ────────────── Total Payments (All Time) ──────────────
            $totalPaymentsAll = Transaction::where('branch_id', $branch->id)
                ->where('type', '+')
                ->sum('amount');

            return [
                'branch_name'             => $branch->name,
                'totalDoctors'            => $totalDoctors,
                'totalPatients'           => $totalPatients,
                'totalConsultationsToday' => $totalConsultationsToday,
                'totalSessionsToday'      => $totalSessionsToday,
                'checkupPaymentsToday'    => $checkupPaymentsToday,
                'sessionPaymentsToday'    => $sessionPaymentsToday,
                'totalPaymentsToday'      => $totalPaymentsToday,
                'totalPaymentsAll'        => $totalPaymentsAll, // ✅ new field
            ];
        });

        return view('admin.dashboard', compact('branchStats'));
    }
}

