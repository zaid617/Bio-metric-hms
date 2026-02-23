<?php

namespace App\Helpers;

use App\Models\Transaction;
use App\Models\Bank;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class ExpenseHelper
{
    /**
     * Handle expense transaction and update balances
     */
    public static function handleExpense(
        int $branch_id,
        int $bank_id,
        float $amount,
        int $payment_type, // 1 = Cash, 2 = Bank
        string $remarks,
        int $entry_by
    ) {
        return DB::transaction(function () use (
            $branch_id,
            $bank_id,
            $amount,
            $payment_type,
            $remarks,
            $entry_by
        ) {
            $branch = Branch::find($branch_id);
            $bank   = $bank_id > 0 ? Bank::find($bank_id) : null;

            if (!$branch) {
                throw new \Exception("Branch not found.");
            }

            // Previous balances
            $prevBranch = $branch->balance ?? 0;
            $prevBank   = $bank->balance ?? 0;

            $postBranch = $prevBranch;
            $postBank   = $prevBank;

            // Cash Transaction
            if ($payment_type === 1) { // Cash
                $postBranch += $amount;
            }

            // Bank Transaction
            if ($payment_type === 2 && $bank) { // Bank
                $postBank += $amount;
                $postBranch += $amount; // branch operational fund
                $bank->update(['balance' => $postBank]);
            }

            // Update branch balance
            $branch->update(['balance' => $postBranch]);

            // Last total balance
            $lastTransaction = Transaction::latest('id')->first();
            $lastTotal = $lastTransaction?->post_total_balance ?? 0;
            $postTotal = $lastTotal + $amount;

            // Create transaction record
            Transaction::create([
                'payment_type'        => $payment_type,
                'invoice_id'          => null,
                'bank_id'             => $bank_id,
                'branch_id'           => $branch_id,
                'patient_id'          => null,
                'doctor_id'           => null,
                'amount'              => $amount,
                'type'                => '+',
                'post_cash_balance'   => $payment_type === 1 ? $postBranch : 0,
                'post_bank_balance'   => $payment_type === 2 ? $postBank : 0,
                'post_branch_balance' => $postBranch,
                'post_total_balance'  => $postTotal,
                'entry_by'            => $entry_by,
                'Remx'                => $remarks,
            ]);

            return true;
        });
    }
}
 