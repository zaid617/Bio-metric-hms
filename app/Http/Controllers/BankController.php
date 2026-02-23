<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    // Show all banks
    //banks changes
    public function index()
    {
        $banks = Bank::all();
        return view('banks.index', compact('banks'));
    }

    // Show create form
    //
    public function create()
    {
        return view('banks.create');
    }

    // Store new bank
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'bank_name' => 'required',
                'account_no' => 'required',
                'account_title' => 'required',
                'balance' => 'required|numeric',
            ]);

            $bank = Bank::create([
                'bank_name' => $request->bank_name,
                'account_no' => $request->account_no,
                'account_title' => $request->account_title,
            ]);

            if ($request->balance > 0) {
                createOpeningBalance(
                branch_id: null,
                bank_id: $bank->id,
                patient_id: null,
                doctor_id: null,
                type: '+',
                amount: $request->balance ?? 0,
                note: 'Opening Balance for Bank ID ' . $bank->id,
                invoice_id: null,
                payment_type: 0,
                entry_by: auth()->id()
            );
            }


            DB::commit();
            return redirect()->route('banks.index')->with('success', 'Bank added successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bank creation error: ' . $e->getMessage());
            return redirect()->route('banks.index')->with('error', $e->getMessage());
        }
    }

    // Show details
    public function show($id)
    {
        $bank = Bank::findOrFail($id);
        return view('banks.show', compact('bank'));
    }

    // Show edit form
    public function edit($id)
    {
        $bank = Bank::findOrFail($id);
        return view('banks.edit', compact('bank'));
    }

    // Update bank
    public function update(Request $request, $id)
    {
        $request->validate([
            'bank_name' => 'required',
            'account_no' => 'required',
            'account_title' => 'required',
            'balance' => 'required|numeric',
        ]);

        $bank = Bank::findOrFail($id);
        $bank->update($request->all());

        return redirect()->route('banks.index')->with('success', 'Bank updated successfully.');
    }

    // Delete bank
    public function destroy($id)
    {
        $bank = Bank::findOrFail($id);
        $bank->delete();

        return redirect()->route('banks.index')->with('success', 'Bank deleted successfully.');
    }
}
