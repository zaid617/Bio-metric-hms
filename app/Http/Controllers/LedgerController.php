<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LedgerController extends Controller
{
    // 🧮 Show Ledger Page
    public function index()
    {
        // 🔹 Branch list dropdown ke liye
        $branches = DB::table('branches')->select('id', 'name')->get();

        // ✅ Latest 100 transactions (sirf branch side walay)
        $transactions = DB::table('transactions')
            ->whereNotNull('branch_id') // ❗ Sirf branch walay records
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        return view('ledger.index', compact('transactions', 'branches'));
    }

    // 🔍 Filter Ledger (Branch + Date Range)
    public function filter(Request $request)
    {
        $branch_id = $request->branch_id;
        $from_date = $request->from_date;
        $to_date   = $request->to_date;

        // ✅ Base Query (sirf branch side walay)
        $query = DB::table('transactions')->whereNotNull('branch_id');

        // 🔹 Branch filter
        if ($branch_id && $branch_id != 'all') {
            $query->where('branch_id', $branch_id);
        }

        // 🔹 Date range filter
        if ($from_date && $to_date) {
            $query->whereBetween(DB::raw('DATE(created_at)'), [$from_date, $to_date]);
        }

        // ✅ Filtered transactions (branch-only)
        $transactions = $query->orderBy('id', 'desc')->get();

        // Branch list for dropdown
        $branches = DB::table('branches')->select('id', 'name')->get();

        return view('ledger.index', compact(
            'transactions', 'branches', 'branch_id', 'from_date', 'to_date'
        ));
    }
}
