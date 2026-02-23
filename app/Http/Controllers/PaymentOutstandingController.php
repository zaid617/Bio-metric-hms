<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TreatmentSession;
use App\Models\Transaction;
use App\Models\Bank;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use App\Models\Appointment;
use App\Models\Checkup;


class PaymentOutstandingController extends Controller
{
    /**
     * Show outstanding (unpaid) invoices.
     */
    public function index()
    {
        // Sirf un treatment sessions ko show kare jinke dues_amount > 0 hain
        $outstandings = TreatmentSession::with('patient')
            ->where('dues_amount', '>', 0)
            ->get();

        return view('payments.outstandings', compact('outstandings'));
    }

    /**
     * Show fully paid invoices.
     */
    public function completedInvoices()
    {
        $outstandings = TreatmentSession::with('patient')
            ->where('dues_amount', '=', 0)
            ->get();

        return view('payments.outstandings', compact('outstandings'));
    }

    /**
     * Show a single invoice ledger with payment details.
     */
    public function invoiceLedger($session_id)
    {
        $session = TreatmentSession::with(['patient', 'transactions' => function ($query) {
            $query->where('payment_type', 2);
        }])->findOrFail($session_id);

        $transactions = $session->transactions;
        $total_amount = $session->session_fee;
        $paid_amount = $transactions->sum('amount');
        $remaining_amount = $total_amount - $paid_amount;
        $banks = Bank::all();

        return view('payments.invoice_ledger', compact(
            'session',
            'transactions',
            'total_amount',
            'paid_amount',
            'remaining_amount',
            'banks'
        ));
    }

    //checkupLedger
    public function checkupInvoiceLedger($checkup_id)
{
    $checkup = Appointment::with('patient')->findOrFail($checkup_id);
    $banks = Bank::all();

    return view('payments.invoice_ledger', [
        'session' => $checkup, // Blade me $session variable se access
        'banks' => $banks
    ]);
}


    /**
     * Add a new payment against a treatment session.
     */
    public function addPayment(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'session_id' => 'required|exists:treatment_sessions,id',
                'amount' => 'required|numeric|min:1',
                'remark' => 'nullable|string|max:255',
                'payment_method' => 'nullable|string|max:100',
            ]);

            $session = TreatmentSession::findOrFail($request->session_id);
            $remaining = $session->remainingAmount();

            if ($request->amount > $remaining) {
                return redirect()->back()->with('error', 'Payment amount exceeds remaining balance.');
            }

            // Create Transaction entry
            handleGeneralTransaction(
                branch_id: $session->branch_id,
                bank_id: $request->payment_method,
                patient_id: $session->patient_id,
                doctor_id: $session->doctor_id,
                type: '+',
                amount: $request->amount ?? 0,
                 note: $request->remarks,
                invoice_id: $request->session_id,
                payment_type: 2,
                entry_by: auth()->id()
            );

            // Update TreatmentSession amounts
            $session->paid_amount += $request->amount;
            $session->dues_amount = max(0, $session->session_fee - $session->paid_amount);

            if ($session->dues_amount == 0) {
                $session->payment_status = 'paid';
            }

            $session->save();

            DB::commit();
            return redirect()->back()->with('success', 'Payment added successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Add Payment error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to add payment. Please try again.');
        }
    }

    /**
     * Show all returned payments (refunds).
     */
    public function returnPayments()
    {
        $returnedPayments = Transaction::where('payment_type', 3)
            ->with(['patient', 'bank'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('payments.search_patient', compact('returnedPayments'));
    }

    /**
     * AJAX: Search patient by name or MR number.
     */
 /**
 * AJAX: Search patient by name or MR number (direct from patients table)
 */
public function searchPatient(Request $request)
{
    $query = trim($request->get('q', ''));

    if ($query === '') {
        return response()->json(['data' => []]);
    }

    $patients = Patient::where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('mr', 'LIKE', "%{$query}%");
        })
        ->orderBy('name', 'asc')
        ->limit(20)
        ->get(['id', 'mr', 'name', 'phone', 'age']); // sirf required columns

    return response()->json(['data' => $patients]);
}



    /**
     * AJAX: Fetch a patient's all payments (for display in modal/table).
     */
    public function fetchPatientPayments(Request $request)
{
    $patientId = $request->get('id');

    $checkups = \App\Models\Checkup::with('patient')
                ->where('patient_id', $patientId)
                ->orderByDesc('created_at')
                ->get();

    $treatments = TreatmentSession::with('patient')
                ->where('patient_id', $patientId)
                ->orderByDesc('created_at')
                ->get();

    $html = view('payments._payments_table', compact('checkups', 'treatments'))->render();

    return response()->json(['html' => $html]);
}

public function invoiceLedgerr($session_id)
    {
        $session = TreatmentSession::with(['patient', 'transactions' => function ($query) {
            $query->whereIn('payment_type', [3,2]);
        }])->findOrFail($session_id);

        $transactions = $session->transactions;
        $total_amount = $session->session_fee;
        $paid_amount = $transactions->where('type', '+')->sum('amount');
        $remaining_amount = $total_amount - $paid_amount;
        $banks = Bank::all();

        return view('payments.return_payment', compact(
            'session',
            'transactions',
            'total_amount',
            'paid_amount',
            'remaining_amount',
            'banks'
        ));
    }
    public function returnPayment(Request $request)
{
    try {
        DB::beginTransaction();

        // Validate input
        $request->validate([
            'session_id' => 'required|exists:treatment_sessions,id',
            'amount' => 'required|numeric|min:1',
            'remark' => 'nullable|string|max:255', 
            'payment_method' => 'nullable|integer', // Cash=0 or Bank ID
        ]);

        $session = TreatmentSession::findOrFail($request->session_id);

        // Check refund amount
        if ($request->amount > $session->paid_amount) {
            return redirect()->back()->with('error', 'Refund amount exceeds the paid amount.');
        }

        $bankId = $request->payment_method; // 0 for cash, else bank ID

        // Create refund transaction
        handleGeneralTransaction(
            branch_id: $session->branch_id,
            bank_id: $bankId,
            patient_id: $session->patient_id,
            doctor_id: $session->doctor_id,
            type: '-', // minus for refund
            amount: $request->amount,
            note: $request->remarks ?? 'Refund for Treatment Session #' . $session->id, // <- yaha fix

            invoice_id: $session->id,
            payment_type: 3, // refund
            entry_by: auth()->id()
        );

        // Update TreatmentSession amounts
        $session->paid_amount -= $request->amount;
        $session->dues_amount = max(0, $session->session_fee - $session->paid_amount);

        // Update payment status
        if ($session->paid_amount == 0) {
            $session->payment_status = 'unpaid';
        } elseif ($session->dues_amount == 0) {
            $session->payment_status = 'paid';
        } else {
            $session->payment_status = 'unpaid';
        }

        $session->save();

        DB::commit();
        return redirect()->back()->with('success', 'Refund processed successfully!');
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Return Payment error: ' . $e->getMessage());
        return redirect()->back()->with('error', $e->getMessage());
    }
}

/**
 * Show a single checkup invoice/ledger
 */
public function invoiceLedgerCheckup($checkup_id)
{
    $checkup = Checkup::with(['patient', 'doctor'])
        ->findOrFail($checkup_id);

    $transactions = Transaction::where('invoice_id', $checkup_id)
        ->whereIn('payment_type', [2,3]) // 2 = payment, 3 = refund
        ->get();

    $total_amount = $checkup->fee;
    $paid_amount = $transactions->where('type', '+')->sum('amount');
    $banks = Bank::all();

    return view('payments.checkup_invoice', compact(
        'checkup',
        'transactions',
        'total_amount',
        'paid_amount',
        'banks'
    ));
}

/**
 * Process refund for a checkup
 */
public function returnCheckupPayment(Request $request)
{
    try {
        DB::beginTransaction();

        $request->validate([
            'checkup_id' => 'required|exists:checkups,id',
            'amount' => 'required|numeric|min:1',
            'remark' => 'nullable|string|max:255',
            'payment_method' => 'nullable|integer', // Cash=0 or Bank ID
        ]);

        $checkup = Checkup::findOrFail($request->checkup_id);


        $bankId = $request->payment_method;

        // Create refund transaction
        handleGeneralTransaction(
            branch_id: $checkup->branch_id,
            bank_id: $bankId,
            patient_id: $checkup->patient_id,
            doctor_id: $checkup->doctor_id,
            type: '-', // minus for refund
            amount: $request->amount,
            note: $request->remark ?? 'Refund for Checkup #' . $checkup->id,
            invoice_id: $checkup->id,
            payment_type: 3, // refund
            entry_by: auth()->id()
        );

        // Update only paid_amount
        $checkup->paid_amount -= $request->amount;
        $checkup->save();

        DB::commit();
        return redirect()->back()->with('success', 'Refund processed successfully!');
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Checkup Refund error: ' . $e->getMessage());
        return redirect()->back()->with('error', $e->getMessage());
    }
}
}