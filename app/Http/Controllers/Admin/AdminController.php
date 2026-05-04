<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Checkup;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

            // ────────────── Total Payments (Current Month) ──────────────
            $totalPaymentsAll = Transaction::where('branch_id', $branch->id)
                ->where('type', '+')
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('amount');

            $consultationPendingTotal = Checkup::where('branch_id', $branch->id)
                ->selectRaw('SUM(CASE WHEN pending_amount IS NOT NULL THEN pending_amount ELSE CASE WHEN (COALESCE(fee,0)-(COALESCE(fee,0)*(COALESCE(discount,0)/100))-COALESCE(paid_amount,0)) > 0 THEN (COALESCE(fee,0)-(COALESCE(fee,0)*(COALESCE(discount,0)/100))-COALESCE(paid_amount,0)) ELSE 0 END END) as total_pending')
                ->value('total_pending') ?? 0;

            return [
                'branch_id'               => $branch->id,
                'branch_name'             => $branch->name,
                'totalDoctors'            => $totalDoctors,
                'totalPatients'           => $totalPatients,
                'totalConsultationsToday' => $totalConsultationsToday,
                'totalSessionsToday'      => $totalSessionsToday,
                'checkupPaymentsToday'    => $checkupPaymentsToday,
                'sessionPaymentsToday'    => $sessionPaymentsToday,
                'totalPaymentsToday'      => $totalPaymentsToday,
                'totalPaymentsAll'        => $totalPaymentsAll, // ✅ new field
                'consultationPendingTotal' => $consultationPendingTotal,
            ];
        });

        return view('admin.dashboard', compact('branchStats'));
    }

    public function branchStatsByDate(Request $request)
    {

        $branchId = $request->branch_id;
        $date = $request->date;
        $date = Carbon::parse($date);

        $transactions = Transaction::where('branch_id', $branchId)
            ->whereDate('created_at', $date);

        $sessionsCount = (clone $transactions)->where('payment_type', 2)->count();
        $sessionsAmount = (clone $transactions)
            ->where('payment_type', 2)
            ->where('type', '+')
            ->sum('amount');

        $consultationsCount = (clone $transactions)->where('payment_type', 1)->count();
        $consultationsAmount = (clone $transactions)
            ->where('payment_type', 1)
            ->where('type', '+')
            ->sum('amount');

        return response()->json([
            'sessions' => [
                'count' => $sessionsCount,
                'amount' => $sessionsAmount,
            ],
            'consultations' => [
                'count' => $consultationsCount,
                'amount' => $consultationsAmount,
            ],
        ]);
    }
}

