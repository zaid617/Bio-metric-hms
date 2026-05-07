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

        $branchId = (int) $request->input('branch_id');
        if ($branchId <= 0) {
            return response()->json([
                'message' => 'branch_id is required',
            ], 422);
        }

        $dateInput = $request->input('date');
        $date = $dateInput ? Carbon::parse($dateInput) : Carbon::today();

        $transactionsForDay = Transaction::where('branch_id', $branchId)
            ->whereDate('created_at', $date);

        $sessionsCount = (clone $transactionsForDay)->where('payment_type', 2)->count();
        $sessionsAmount = (clone $transactionsForDay)
            ->where('payment_type', 2)
            ->where('type', '+')
            ->sum('amount');

        $consultationsCount = (clone $transactionsForDay)->where('payment_type', 1)->count();
        $consultationsAmount = (clone $transactionsForDay)
            ->where('payment_type', 1)
            ->where('type', '+')
            ->sum('amount');

        $totalPaymentsToday = (clone $transactionsForDay)
            ->where('type', '+')
            ->sum('amount');

        $totalPaymentsAll = Transaction::where('branch_id', $branchId)
            ->where('type', '+')
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->sum('amount');

        $consultationPendingTotal = Checkup::where('branch_id', $branchId)
            ->selectRaw('SUM(CASE WHEN pending_amount IS NOT NULL THEN pending_amount ELSE CASE WHEN (COALESCE(fee,0)-(COALESCE(fee,0)*(COALESCE(discount,0)/100))-COALESCE(paid_amount,0)) > 0 THEN (COALESCE(fee,0)-(COALESCE(fee,0)*(COALESCE(discount,0)/100))-COALESCE(paid_amount,0)) ELSE 0 END END) as total_pending')
            ->value('total_pending') ?? 0;

        // Keep existing nested response for compatibility, and add top-level fields
        // expected by the dashboard AJAX handler.
        return response()->json([
            'sessions' => [
                'count' => $sessionsCount,
                'amount' => $sessionsAmount,
            ],
            'consultations' => [
                'count' => $consultationsCount,
                'amount' => $consultationsAmount,
            ],
            'totalSessionsToday' => $sessionsCount,
            'totalConsultationsToday' => $consultationsCount,
            'totalPaymentsToday' => $totalPaymentsToday,
            'totalPaymentsAll' => $totalPaymentsAll,
            'consultationPendingTotal' => $consultationPendingTotal,
        ]);
    }
}

