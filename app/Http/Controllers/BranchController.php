<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class BranchController extends Controller
{
    // List all branches
    public function index()
    {
        $branches = Branch::all();
        return view('branches.index', compact('branches'));
    }

    // Show create form
    public function create()
    {
        return view('branches.create');
    }

    // Store new branch
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'address'         => 'nullable|string',
            'prefix'          => 'nullable|string',
            'phone'           => 'nullable|string|max:20',
            'status'          => 'required|string|in:active,inactive',
            'fee'             => 'required|numeric|min:0',
            'opening_balance' => 'required|numeric|min:0',
            'city'            => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Branch create
            $branch = Branch::create([
                'name'             => $request->name,
                'address'          => $request->address,
                'prefix'           => $request->prefix,
                'phone'            => $request->phone,
                'status'           => $request->status,
                'fee'              => $request->fee,
                'balance'          => 0,
                'city'             => $request->city,
            ]);

            if ($request->opening_balance > 0) {
                    createOpeningBalance(
                    branch_id: $branch->id,
                    bank_id: 0,
                    patient_id: null,
                    doctor_id: null,
                    type: '+',
                    amount: $request->opening_balance ?? 0,
                    note: 'Opening Balance for Branch ID ' . $branch->id,
                    invoice_id: null,
                    payment_type: 0,
                    entry_by: auth()->id()
                );
            }


            DB::commit();

            return redirect()->route('branches.index')->with('success', 'Branch added successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    // Show edit form
    public function edit($id)
    {
        $branch = Branch::findOrFail($id);
        return view('branches.edit', compact('branch'));
    }

    // Update branch
    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'prefix' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|string|in:active,inactive',
            'fee' => 'required|numeric|min:0', // Fee validation added
        ]);

        $branch->update($request->all());

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    // Delete branch
    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }
}
