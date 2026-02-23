<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentTransactionController extends Controller
{
    public function index()
    {
        $branches = DB::table('branches')->get();
        $banks    = DB::table('banks')->get();

        return view('payments.transfer', compact('branches', 'banks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_type' => 'required|in:bank,branch',
            'from_id'   => 'required|integer',
            'to_type'   => 'required|in:bank,branch',
            'to_id'     => 'required|integer',
            'amount'    => 'required|numeric|min:1',
        ]);

        if ($request->from_type === $request->to_type && $request->from_id == $request->to_id) {
            return back()->with('error', 'From and To accounts cannot be the same.');
        }

        $amount   = $request->amount;
        $fromType = $request->from_type;
        $toType   = $request->to_type;
        $fromId   = $request->from_id;
        $toId     = $request->to_id;

        try {
            DB::transaction(function () use ($amount, $fromType, $toType, $fromId, $toId) {

                $userId = auth()->id() ?? 1;

                // 🔹 Fetch latest sender balance for transfer
                $fromLast = DB::table('transactions')
                    ->where($fromType.'_id', $fromId)
                    ->where('payment_method', 'transfer')
                    ->latest('id')
                    ->first();

                $fromCashBalance   = $fromLast->post_cash_balance ?? 0;
                $fromBankBalance   = $fromType === 'bank' ? ($fromLast->post_bank_balance ?? DB::table('banks')->where('id', $fromId)->value('balance') ?? 0) : 0;
                $fromBranchBalance = $fromType === 'branch' ? ($fromLast->post_branch_balance ?? DB::table('branches')->where('id', $fromId)->value('balance') ?? 0) : 0;
                $fromTotalBalance  = $fromCashBalance + $fromBankBalance + $fromBranchBalance;

                if ($fromType === 'bank' && $fromBankBalance < $amount) {
                    throw new \Exception("Insufficient balance in bank account (Available: {$fromBankBalance})");
                }
                if ($fromType === 'branch' && $fromBranchBalance < $amount) {
                    throw new \Exception("Insufficient balance in branch account (Available: {$fromBranchBalance})");
                }

                // 🔹 Fetch latest receiver balance for transfer
                $toLast = DB::table('transactions')
                    ->where($toType.'_id', $toId)
                    ->where('payment_method', 'transfer')
                    ->latest('id')
                    ->first();

                $toCashBalance   = $toLast->post_cash_balance ?? 0;
                $toBankBalance   = $toType === 'bank' ? ($toLast->post_bank_balance ?? DB::table('banks')->where('id', $toId)->value('balance') ?? 0) : 0;
                $toBranchBalance = $toType === 'branch' ? ($toLast->post_branch_balance ?? DB::table('branches')->where('id', $toId)->value('balance') ?? 0) : 0;
                $toTotalBalance  = $toCashBalance + $toBankBalance + $toBranchBalance;

                // 🔹 Calculate new balances
                $newFromBankBalance   = $fromType === 'bank' ? $fromBankBalance - $amount : $fromBankBalance;
                $newFromBranchBalance = $fromType === 'branch' ? $fromBranchBalance - $amount : $fromBranchBalance;
                $newFromTotalBalance  = $fromCashBalance + $newFromBankBalance + $newFromBranchBalance;

                $newToBankBalance   = $toType === 'bank' ? $toBankBalance + $amount : $toBankBalance;
                $newToBranchBalance = $toType === 'branch' ? $toBranchBalance + $amount : $toBranchBalance;
                $newToTotalBalance  = $toCashBalance + $newToBankBalance + $newToBranchBalance;

                // 🔹 Account names
                $fromName = $fromType === 'bank'
                    ? DB::table('banks')->where('id', $fromId)->value('bank_name')
                    : DB::table('branches')->where('id', $fromId)->value('name');

                $toName = $toType === 'bank'
                    ? DB::table('banks')->where('id', $toId)->value('bank_name')
                    : DB::table('branches')->where('id', $toId)->value('name');

                // 🔹 Insert sender transaction (Transfer Out)
                DB::table('transactions')->insert([
                    'payment_type'        => 6,
                    'payment_method'      => 'transfer',
                    'bank_id'             => $fromType === 'bank' ? $fromId : null,
                    'branch_id'           => $fromType === 'branch' ? $fromId : null,
                    'amount'              => $amount,
                    'type'                => '-', // Transfer Out
                    'post_cash_balance'   => $fromCashBalance,
                    'post_bank_balance'   => $newFromBankBalance,
                    'post_branch_balance' => $newFromBranchBalance,
                    'post_total_balance'  => $newFromTotalBalance,
                    'entry_by'            => $userId,
                    'Remx'                => "Transfer from {$fromType} '{$fromName}' to {$toType} '{$toName}'",
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                // 🔹 Insert receiver transaction (Transfer In)
                DB::table('transactions')->insert([
                    'payment_type'        => 6,
                    'payment_method'      => 'transfer',
                    'bank_id'             => $toType === 'bank' ? $toId : null,
                    'branch_id'           => $toType === 'branch' ? $toId : null,
                    'amount'              => $amount,
                    'type'                => '+', // Transfer In
                    'post_cash_balance'   => $toCashBalance,
                    'post_bank_balance'   => $newToBankBalance,
                    'post_branch_balance' => $newToBranchBalance,
                    'post_total_balance'  => $newToTotalBalance,
                    'entry_by'            => $userId,
                    'Remx'                => "Received transfer from {$fromType} '{$fromName}'",
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

            }, 5);

            return back()->with('success', 'Transfer completed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
