<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankLedgerController extends Controller
{
    // 🏦 Show Bank Ledger
    public function index()
    {
        // Join transactions with banks table
        $transactions = DB::table('transactions')
            ->leftJoin('banks', 'transactions.bank_id', '=', 'banks.id')
            ->select('transactions.*', 'banks.bank_name')
            ->whereNotNull('transactions.bank_id')
            ->orderBy('transactions.id', 'desc')
            ->limit(100)
            ->get();

        // Unique banks for dropdown
        $banks = DB::table('banks')->select('id', 'bank_name')->get();

        // Totals
        $totalDeposit = $transactions->where('type', '+')->sum('amount');
        $totalWithdrawal = $transactions->where('type', '-')->sum('amount');

        return view('bank_ledger.index', compact(
            'transactions', 'banks', 'totalDeposit', 'totalWithdrawal'
        ));
    }

    // 🔍 Filter Bank Ledger
    public function filter(Request $request)
    {
        $bank_id = $request->bank_id;
        $from_date = $request->from_date;
        $to_date   = $request->to_date;

        // Base query
        $query = DB::table('transactions')
            ->leftJoin('banks', 'transactions.bank_id', '=', 'banks.id')
            ->select('transactions.*', 'banks.bank_name')
            ->whereNotNull('transactions.bank_id');

        // Bank filter
        if ($bank_id && $bank_id != 'all') {
            $query->where('transactions.bank_id', $bank_id);
        }

        // Date range filter
        if ($from_date && $to_date) {
            $query->whereBetween(DB::raw('DATE(transactions.created_at)'), [$from_date, $to_date]);
        }

        $transactions = $query->orderBy('transactions.id', 'desc')->get();

        // Unique banks for dropdown
        $banks = DB::table('banks')->select('id', 'bank_name')->get();

        // Totals
        $totalDeposit = $transactions->where('type', '+')->sum('amount');
        $totalWithdrawal = $transactions->where('type', '-')->sum('amount');

        return view('bank_ledger.index', compact(
            'transactions', 'banks', 'bank_id', 'from_date', 'to_date',
            'totalDeposit', 'totalWithdrawal'
        ));
    }
}
