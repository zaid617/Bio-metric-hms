<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\ExpenseHelper;
use App\Models\Branch;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // =======================
    // EXPENSE LIST
    // =======================
    public function index()
    {
        $user = auth()->user();
        $expenses = DB::table('expenses')
            ->join('expense_types', 'expenses.expense_type_id', '=', 'expense_types.id')
            ->select('expenses.*', 'expense_types.type as expense_type')
            ->when(!user_can_manage_all_branches($user), function ($query) use ($user) {
                $query->where('expenses.branch_id', user_branch_id($user));
            })
            ->get();

        return view('expenses.index', compact('expenses'));
    }

    // =======================
    // SHOW CREATE FORM
    // =======================
    public function create()
    {
        $types = DB::table('expense_types')->where('status', 1)->get();
        $user = auth()->user();

        $isAdmin = user_can_manage_all_branches($user);

        // Admin/Super Admin → all branches, others stay on their own branch
        $branches = $isAdmin ? Branch::all() : Branch::where('id', user_branch_id($user))->get();

        return view('expenses.create', compact('types', 'branches'));
    }

    // =======================
    // STORE EXPENSE
    // =======================
    public function store(Request $request)
    {
        DB::transaction(function() use ($request) {

            $user = auth()->user();

            // Branch logic (Admin selects manually, User auto)
            $branch_id = user_can_manage_all_branches($user)
                ? ($request->branch_id ?? null)
                : user_branch_id($user);

            // Insert into expenses table
            $expenseId = DB::table('expenses')->insertGetId([
                'branch_id'       => $branch_id,
                'expense_type_id' => $request->expense_type_id,
                'amount'          => $request->amount,
                'method'          => $request->method,
                'remarks'         => $request->remarks,
                'created_by'      => $user->id ?? 0,
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ]);

            // Bank ID (only if method = Bank)
            $bank_id = $request->method === 'Bank' ? ($request->bank_id ?? 0) : 0;

            // Payment type (for accounts)
            $payment_type = $request->method === 'Cash' ? 1 : 2;

            // Call helper function
            ExpenseHelper::handleExpense(
                $branch_id,
                $bank_id,
                $request->amount,
                $payment_type,
                "Expense ID: {$expenseId} | {$request->remarks}",
                $user->id ?? 0
            );

        });

        return redirect()->route('expenses.index')
                         ->with('success', 'Expense Added & Transaction Recorded Successfully');
    }
}
