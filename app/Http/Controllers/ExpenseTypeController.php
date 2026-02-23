<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseTypeController extends Controller
{
    public function index()
    {
        $types = DB::table('expense_types')->get();
        return view('expenses.types.index', compact('types'));
    }

    public function store(Request $request)
    {
        DB::table('expense_types')->insert([
            'type' => $request->type,
            'status' => $request->status,
        ]);

        return back()->with('success', 'Expense Type Added');
    }
}
