<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TreatmentSession;
use App\Models\Transaction;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Checkup;


class PaymentOutstandingController extends Controller
{
    private function isSuperAdmin(): bool
    {
        $user = auth()->user();

        return (bool) ($user && method_exists($user, 'hasRole') && $user->hasRole('admin'));
    }

    private function selectedBranchId(Request $request): ?int
    {
        $user = auth()->user();
        $requestedBranchId = (int) $request->query('branch_id', 0);

        if ($this->isSuperAdmin()) {
            return $requestedBranchId > 0 ? $requestedBranchId : null;
        }

        return !empty($user?->branch_id) ? (int) $user->branch_id : null;
    }

    private function applySessionFilters($query, Request $request)
    {
        $selectedBranchId = $this->selectedBranchId($request);
        $search = trim((string) $request->query('q', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if (!empty($selectedBranchId)) {
            $query->where('branch_id', $selectedBranchId);
        }

        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('id', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('mr', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function applyCheckupFilters($query, Request $request)
    {
        $selectedBranchId = $this->selectedBranchId($request);
        $search = trim((string) $request->query('q', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $status = strtolower((string) $request->query('status', 'all'));
        $createdBy = (int) $request->query('created_by', 0);

        $pendingSql = "CASE WHEN pending_amount IS NOT NULL THEN pending_amount ELSE CASE WHEN (COALESCE(fee,0)-(COALESCE(fee,0)*(COALESCE(discount,0)/100))-COALESCE(paid_amount,0)) > 0 THEN (COALESCE(fee,0)-(COALESCE(fee,0)*(COALESCE(discount,0)/100))-COALESCE(paid_amount,0)) ELSE 0 END END";

        $query->where(function ($q) {
            // Some records are saved as Enrollment/appointment variants.
            $q->whereNull('consultation_type')
                ->orWhereRaw("TRIM(COALESCE(consultation_type, '')) = ''")
                ->orWhereRaw("LOWER(TRIM(consultation_type)) in ('appointment', 'enrollment')");
        });

        if (!empty($selectedBranchId)) {
            $query->where('branch_id', $selectedBranchId);
        }

        if ($createdBy > 0) {
            $query->where('created_by', $createdBy);
        }

        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('id', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('mr', 'like', "%{$search}%");
                    });
            });
        }

        if ($status === 'outstanding') {
            $query->whereRaw("{$pendingSql} > 0");
        } elseif ($status === 'paid') {
            $query->whereRaw("{$pendingSql} <= 0");
        }

        return $query;
    }

    /**
     * Show outstanding (unpaid) invoices.
     */
    public function index(Request $request)
    {
        $outstandings = $this->applySessionFilters(TreatmentSession::with('patient'), $request)
            ->where('dues_amount', '>', 0)
            ->orderByDesc('created_at')
            ->get();

        $isSuperAdmin = $this->isSuperAdmin();
        $branches = $isSuperAdmin ? Branch::orderBy('name')->get(['id', 'name']) : collect();
        $selectedBranchId = $this->selectedBranchId($request);
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        return view('payments.outstandings', [
            'outstandings' => $outstandings,
            'isSuperAdmin' => $isSuperAdmin,
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'filters' => $filters,
            'subtitle' => 'Outstanding Payments',
        ]);
    }

    /**
     * Show fully paid invoices.
     */
    public function completedInvoices(Request $request)
    {
        $outstandings = $this->applySessionFilters(TreatmentSession::with('patient'), $request)
            ->where('dues_amount', '=', 0)
            ->orderByDesc('created_at')
            ->get();

        $isSuperAdmin = $this->isSuperAdmin();
        $branches = $isSuperAdmin ? Branch::orderBy('name')->get(['id', 'name']) : collect();
        $selectedBranchId = $this->selectedBranchId($request);
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        return view('payments.outstandings', [
            'outstandings' => $outstandings,
            'isSuperAdmin' => $isSuperAdmin,
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'filters' => $filters,
            'subtitle' => 'Completed Invoices',
        ]);
    }

    /**
     * Show payment receivables (same dataset as outstanding invoices).
     */
    public function receivable(Request $request)
    {
        $outstandings = $this->applySessionFilters(TreatmentSession::with('patient'), $request)
            ->where('dues_amount', '>', 0)
            ->orderByDesc('created_at')
            ->get();

        $isSuperAdmin = $this->isSuperAdmin();
        $branches = $isSuperAdmin ? Branch::orderBy('name')->get(['id', 'name']) : collect();
        $selectedBranchId = $this->selectedBranchId($request);
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        return view('payments.outstandings', [
            'outstandings' => $outstandings,
            'isSuperAdmin' => $isSuperAdmin,
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'filters' => $filters,
            'subtitle' => 'Payment Receivable',
        ]);
    }

    /**
     * Show appointment invoices (checkups).
     */
    public function appointmentInvoices(Request $request)
    {
        $appointmentInvoices = $this->applyCheckupFilters(Checkup::with(['patient', 'doctor', 'branch']), $request)
            ->orderByDesc('created_at')
            ->get();

        $isSuperAdmin = $this->isSuperAdmin();
        $branches = $isSuperAdmin ? Branch::orderBy('name')->get(['id', 'name']) : collect();
        $selectedBranchId = $this->selectedBranchId($request);
        $creatorUsers = User::query()
            ->select('id', 'name')
            ->when(!empty($selectedBranchId), function ($userQuery) use ($selectedBranchId) {
                $userQuery->where('branch_id', $selectedBranchId);
            })
            ->orderBy('name')
            ->get();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'status' => strtolower((string) $request->query('status', 'all')),
            'created_by' => (string) $request->query('created_by', ''),
        ];

        return view('payments.appointment_invoices', [
            'appointmentInvoices' => $appointmentInvoices,
            'isSuperAdmin' => $isSuperAdmin,
            'branches' => $branches,
            'creatorUsers' => $creatorUsers,
            'selectedBranchId' => $selectedBranchId,
            'filters' => $filters,
        ]);
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
    $checkup = Checkup::with('patient')->findOrFail($checkup_id);
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
    public function returnPayments(Request $request)
    {
        $selectedBranchId = $this->selectedBranchId($request);
        $isSuperAdmin = $this->isSuperAdmin();
        $search = trim((string) $request->query('q', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $returnedPaymentsQuery = DB::table('transactions as t')
            ->leftJoin('patients as p', 't.patient_id', '=', 'p.id')
            ->leftJoin('branches as br', 't.branch_id', '=', 'br.id')
            ->leftJoin('banks as bk', 't.bank_id', '=', 'bk.id')
            ->where('t.payment_type', 3)
            ->where('t.type', '-')
            ->select([
                't.id',
                't.invoice_id',
                't.amount',
                't.created_at',
                't.branch_id',
                't.Remx',
                'p.name as patient_name',
                'p.mr as patient_mr',
                'br.name as branch_name',
                'bk.bank_name as bank_name',
            ]);

        if (!empty($selectedBranchId)) {
            $returnedPaymentsQuery->where('t.branch_id', $selectedBranchId);
        }

        if (!empty($dateFrom)) {
            $returnedPaymentsQuery->whereDate('t.created_at', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $returnedPaymentsQuery->whereDate('t.created_at', '<=', $dateTo);
        }

        if ($search !== '') {
            $returnedPaymentsQuery->where(function ($inner) use ($search) {
                $inner->where('p.name', 'like', "%{$search}%")
                    ->orWhere('p.mr', 'like', "%{$search}%")
                    ->orWhere('t.invoice_id', 'like', "%{$search}%")
                    ->orWhere('t.id', 'like', "%{$search}%");
            });
        }

        $returnedPayments = $returnedPaymentsQuery
            ->orderBy('created_at', 'desc')
            ->get();

        $branches = $isSuperAdmin ? Branch::orderBy('name')->get(['id', 'name']) : collect();
        $filters = [
            'q' => $search,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        return view('payments.search_patient', compact('returnedPayments', 'branches', 'selectedBranchId', 'isSuperAdmin', 'filters'));
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
    $checkup = DB::table('checkups')
        ->join('patients', 'checkups.patient_id', '=', 'patients.id')
        ->join('doctors', 'checkups.doctor_id', '=', 'doctors.id')
        ->leftJoin('branches', 'checkups.branch_id', '=', 'branches.id')
        ->select(
            'checkups.*',
            'patients.name as patient_name',
            'patients.phone as patient_phone',
            'patients.gender',
            'patients.age as patient_age',
            'patients.mr as patient_mr',
            DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name"),
            'branches.name as branch_name',
            DB::raw("COALESCE(checkups.consultation_type, 'Appointment') as consultation_type_display")
        )
        ->where('checkups.id', $checkup_id)
        ->first();

    if (!$checkup) {
        abort(404, 'Checkup not found.');
    }

    $transactions = Transaction::where('invoice_id', $checkup_id)
        ->whereIn('payment_type', [1, 3]) // 1 = checkup payment, 3 = refund
        ->get();

    $total_amount = (float) ($checkup->fee ?? 0);
    $discount_percent = (float) ($checkup->discount ?? 0);
    $discount_amount = $total_amount * ($discount_percent / 100);
    $net_amount = max(0, $total_amount - $discount_amount);
    $paid_amount = (float) ($checkup->paid_amount ?? 0);
    $pending_amount = \App\Models\Checkup::calculatePendingAmount($total_amount, $discount_percent, $paid_amount);
    $checkup->pending_amount_resolved = $pending_amount;
    $banks = Bank::all();

    return view('consultations.print_custom', compact(
        'checkup',
        'transactions',
        'total_amount',
        'discount_amount',
        'net_amount',
        'paid_amount',
        'pending_amount',
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

        if ((float) $request->amount > (float) ($checkup->paid_amount ?? 0)) {
            return redirect()->back()->with('error', 'Refund amount exceeds the paid amount.');
        }


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

        // Update paid and pending amounts
        $checkup->paid_amount = max(0, (float) ($checkup->paid_amount ?? 0) - (float) $request->amount);
        $checkup->pending_amount = \App\Models\Checkup::calculatePendingAmount(
            (float) ($checkup->fee ?? 0),
            (float) ($checkup->discount ?? 0),
            (float) ($checkup->paid_amount ?? 0)
        );
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
