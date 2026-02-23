<?php

use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Bank;
use App\Models\Branch;


if (!function_exists('createOpeningBalance')) {
    function createOpeningBalance(
        ?int $branch_id,
        ?int $bank_id,
        ?int $patient_id,
        ?int $doctor_id,
        string $type, // '+' or '-'
        float $amount,
        ?string $note = null,
        ?int $invoice_id = null,
        ?int $payment_type = null,
        ?int $entry_by = null
    ) {
        return DB::transaction(function () use (
            $branch_id, $bank_id, $patient_id, $doctor_id,
            $type, $amount, $note, $invoice_id, $payment_type, $entry_by
        ) {

            if ($amount <= 0) {
                throw new Exception("Invalid opening balance amount.");
            }

            $entryBy = $entry_by ?? auth()->id();

            $branch = $branch_id ? Branch::find($branch_id) : null;
            $bank   = ($bank_id && $bank_id != 0) ? Bank::find($bank_id) : null;

            $prevBranch = $branch?->balance ?? 0;
            $prevBank   = $bank?->balance ?? 0;

            $postBranch = $prevBranch;
            $postBank   = $prevBank;

            /**
             * -------------------------------------------------------
             * 🏦 CASE 1: Branch Opening Balance
             * -------------------------------------------------------
             */
            if ($branch_id && !$bank_id) {
                if ($type === '+') {
                    $postBranch = $prevBranch + $amount;
                } else {
                    if ($prevBranch < $amount) throw new Exception("Insufficient branch balance.");
                    $postBranch = $prevBranch - $amount;
                }
                $branch->update(['balance' => $postBranch]);
            }

            /**
             * -------------------------------------------------------
             * 🏧 CASE 2: Bank Opening Balance
             * -------------------------------------------------------
             */
            if ($bank_id && !$branch_id) {
                if ($type === '+') {
                    $postBank = $prevBank + $amount;
                } else {
                    if ($prevBank < $amount) throw new Exception("Insufficient bank balance.");
                    $postBank = $prevBank - $amount;
                }
                $bank->update(['balance' => $postBank]);
            }

            /**
             * -------------------------------------------------------
             * 📊 Get Last System Total (from last transaction)
             * -------------------------------------------------------
             */
            $lastTransaction = Transaction::latest('id')->first();
            $lastTotal = $lastTransaction ? (float)$lastTransaction->post_total_balance : 0.0;

            // Update total based on type
            if ($type === '+') {
                $postTotal = $lastTotal + $amount;
            } else {
                $postTotal = $lastTotal - $amount;
            }

            /**
             * -------------------------------------------------------
             * 🧾 Create Transaction Record
             * -------------------------------------------------------
             */
            Transaction::create([
                'payment_type'        => $payment_type,
                'invoice_id'          => $invoice_id,
                'bank_id'             => $bank_id,
                'branch_id'           => $branch_id,
                'patient_id'          => $patient_id,
                'doctor_id'           => $doctor_id,
                'amount'              => $amount,
                'type'                => $type,
                'post_cash_balance'   => $branch_id ? $postBranch : 0,
                'post_bank_balance'   => $bank_id ? $postBank : 0,
                'post_branch_balance' => $branch_id ? $postBranch : 0,
                'post_total_balance'  => $postTotal,
                'entry_by'            => $entryBy,
                'Remx'                => $note,
            ]);

            return true;
        });
    }
}



if (!function_exists('handleGeneralTransaction')) {
    function handleGeneralTransaction(
        ?int $branch_id,
        ?int $bank_id,
        ?int $patient_id,
        ?int $doctor_id,
        string $type, // '+' or '-'
        float $amount,
        ?string $note = null,
        ?int $invoice_id = null,
        ?int $payment_type = null,
        ?int $entry_by = null
    ) {
        return DB::transaction(function () use (
            $branch_id, $bank_id, $patient_id, $doctor_id,
            $type, $amount, $note, $invoice_id, $payment_type, $entry_by
        ) {
            info('Starting transaction', [
                'branch_id' => $branch_id,
                'bank_id'   => $bank_id,
                'amount'    => $amount,
                'type'      => $type,
                'amount'    => $amount,
                'type'      => $type,
            ]);
            if ($amount <= 0) {
                throw new Exception("Invalid transaction amount.");
            }


            $entryBy = $entry_by ?? auth()->id();

            $branch = $branch_id ? Branch::find($branch_id) : null;
            $bank   = ($bank_id && $bank_id != 0) ? Bank::find($bank_id) : null;

            $prevBranch = $branch?->balance ?? 0;
            $prevBank   = $bank?->balance ?? 0;

            // ✅ Get last cash (branch only, bank_id = 0)
            $lastCashTxn = Transaction::where('branch_id', $branch_id)
                ->where('bank_id', 0)
                ->latest('id')
                ->first();
            $prevCash = $lastCashTxn?->post_cash_balance ?? 0;

            // ✅ Get last total balance from system
            $lastTransaction = Transaction::latest('id')->first();
            $lastTotal = $lastTransaction?->post_total_balance ?? 0.0;

            $postBranch = $prevBranch;
            $postBank   = $prevBank;
            $postCash   = $prevCash;

            // echo 'Point-1--'.$postCash;
            info(['postCash-1' => $postCash, 'postBranch-1' => $postBranch, 'postBank' => $postBank]);

            /**
             * -------------------------------------------------------
             * 💵 CASE 1: CASH Transaction (bank_id = 0)
             * -------------------------------------------------------
             */
            if ($bank_id == 0 && $branch_id) {
                if ($type === '+') {
                    $postBranch += $amount;
                    $postCash += $amount;
                } else {
                    if ($prevCash < $amount) throw new Exception("Insufficient cash balance.");
                    $postBranch -= $amount;
                    $postCash -= $amount;
                }

                // Branch balance also updated (cash reflects physical cash)
                $branch->update(['balance' => $postBranch]);
                //$postBranch = $postCash;

                // echo '<br/>Point-2--'.$postCash;
            }

            /**
             * -------------------------------------------------------
             * 🏦 CASE 2: BANK Transaction (bank_id > 0)
             * -------------------------------------------------------
             */
            if ($bank_id > 0 && $branch_id) {
                if ($type === '+') {
                    $postBank = $prevBank + $amount;
                     $postBranch += $amount;
                } else {
                    if ($prevBank < $amount) throw new Exception("Insufficient bank balance.");
                        $postBank = $prevBank - $amount;
                        $postBranch -= $amount;
                }

                $bank->update(['balance' => $postBank]);
                $branch->update(['balance' => $postBranch]);


                // ✅ Do not double count bank+branch in totals
                // Branch balance reflects operational fund, not direct bank cash
                //$postBranch = $prevBranch;
            }

            info(['postCash-2' => $postCash, 'postBranch-2' => $postBranch]);
            /**
             * -------------------------------------------------------
             * 📊 Calculate Post Total (from last transaction)
             * -------------------------------------------------------
             */
            $postTotal = $type === '+' ? $lastTotal + $amount : $lastTotal - $amount;

            /**
             * -------------------------------------------------------
             * 🧾 Create Transaction Record
             * -------------------------------------------------------
             */


            Transaction::create([
                'payment_type'        => $payment_type,
                'invoice_id'          => $invoice_id,
                'bank_id'             => $bank_id,
                'branch_id'           => $branch_id,
                'patient_id'          => $patient_id,
                'doctor_id'           => $doctor_id,
                'amount'              => $amount,
                'type'                => $type,

                // ✅ Correct post balances
                'post_cash_balance'   => $bank_id == 0 ? $postCash : 0,
                'post_bank_balance'   => $bank_id > 0 ? $postBank : 0,
                'post_branch_balance' => $postBranch,
                'post_total_balance'  => $postTotal,

                'entry_by'            => $entryBy,
                'Remx'                => $note,
            ]);

            return true;
        });
    }
}

















