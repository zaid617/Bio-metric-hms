<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeReportController extends Controller
{
    public function index(Request $request)
    {
        // 🟢 Get filter inputs
        $paymentType = $request->input('payment_type');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $searchUser = $request->input('search_user');

        // 🟢 Base Query (Only income + Appointment + Session)
        $query = DB::table('transactions')
            ->leftJoin('patients', 'transactions.patient_id', '=', 'patients.id')
            ->select(
                'transactions.id',
                'transactions.payment_type',
                'transactions.payment_method',
                'transactions.amount',
                'transactions.branch_id',
                'transactions.Remx as remx',
                'transactions.created_at',
                'patients.name as patient_name'
            )
            ->where('transactions.type', '+')
            ->whereIn('transactions.payment_type', [1, 2]); // ✅ Appointment + Session only

        // 🟢 Filter by Payment Type
        if (!empty($paymentType)) {
            $query->where('transactions.payment_type', $paymentType);
        }

        // 🟢 Filter by Date Range
        if (!empty($fromDate) && !empty($toDate)) {
            $query->whereBetween(DB::raw('DATE(transactions.created_at)'), [$fromDate, $toDate]);
        }

        // 🟢 Filter by Patient Name
        if (!empty($searchUser)) {
            $query->where('patients.name', 'like', '%' . $searchUser . '%');
        }

        // 🟢 Get results
        $incomes = $query->orderBy('transactions.created_at', 'desc')->get();

        $consultationFinancialSummary = DB::table('checkups')
            ->selectRaw('COALESCE(SUM(fee),0) as total_amount')
            ->selectRaw('COALESCE(SUM(COALESCE(fee,0) * (COALESCE(discount,0) / 100)),0) as total_discount')
            ->selectRaw('COALESCE(SUM(paid_amount),0) as total_paid')
            ->selectRaw('COALESCE(SUM(CASE WHEN pending_amount IS NOT NULL THEN pending_amount ELSE CASE WHEN (COALESCE(fee,0)-(COALESCE(fee,0)*(COALESCE(discount,0)/100))-COALESCE(paid_amount,0)) > 0 THEN (COALESCE(fee,0)-(COALESCE(fee,0)*(COALESCE(discount,0)/100))-COALESCE(paid_amount,0)) ELSE 0 END END),0) as total_pending')
            ->when(!empty($fromDate) && !empty($toDate), function ($reportQuery) use ($fromDate, $toDate) {
                $reportQuery->whereBetween(DB::raw('DATE(COALESCE(checkup_date, created_at))'), [$fromDate, $toDate]);
            })
            ->first();

        // 🟢 Dropdown options
        $paymentTypes = [
            1 => 'Appointment',
            2 => 'Session',
        ];

        // ✅ Return to view
        return view('income_report.index', compact(
            'incomes',
            'paymentTypes',
            'paymentType',
            'fromDate',
            'toDate',
            'searchUser',
            'consultationFinancialSummary'
        ));
    }
}
