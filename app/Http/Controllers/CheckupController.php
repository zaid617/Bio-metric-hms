<?php

namespace App\Http\Controllers;

use App\Models\Checkup;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class CheckupController extends Controller
{
    /**
     * Show checkups with filters.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $pendingExpression = $this->pendingAmountExpression();

            $query = DB::table('checkups')
                ->join('patients', 'checkups.patient_id', '=', 'patients.id')
                ->join('doctors', 'checkups.doctor_id', '=', 'doctors.id')
                ->leftJoin('users as creator', 'checkups.created_by', '=', 'creator.id')
                ->leftJoin('doctors as ref', 'checkups.referred_by', '=', 'ref.id')
                ->leftJoin('branches', 'checkups.branch_id', '=', 'branches.id')
                ->select(
                    'checkups.*',
                    'patients.name as patient_name',
                    'patients.gender',
                    'patients.mr',
                    'patients.phone as patient_phone',
                    DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                    'creator.name as created_by_name',
                    DB::raw("COALESCE(NULLIF(checkups.referred_by_name, ''), CONCAT(ref.first_name, ' ', ref.last_name)) as referred_by_name"),
                    'branches.name as branch_name',
                    DB::raw("COALESCE(checkups.consultation_type, 'Appointment') as consultation_type_display"),
                    DB::raw("{$pendingExpression} as pending_amount_resolved")
                );

            if ($user->hasRole('admin')) {
                // Admin sees all checkups.
            } elseif ($user->hasRole('doctor')) {
                $query->where('checkups.doctor_id', $user->id);
            } else {
                $query->where('checkups.branch_id', $user->branch_id);
            }

            $consultationType = $request->input('consultation_type');
            if ($consultationType && in_array($consultationType, $this->consultationTypeOptions(), true)) {
                if ($consultationType === 'Appointment') {
                    $query->where(function ($inner) {
                        $inner->where('checkups.consultation_type', 'Appointment')
                            ->orWhereNull('checkups.consultation_type');
                    });
                } else {
                    $query->where('checkups.consultation_type', $consultationType);
                }
            }

            if ($request->filled('doctor_id')) {
                $query->where('checkups.doctor_id', (int) $request->doctor_id);
            }

            if ($request->filled('patient_search')) {
                $keyword = trim((string) $request->patient_search);
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('patients.name', 'like', "%{$keyword}%")
                        ->orWhere('patients.mr', 'like', "%{$keyword}%")
                        ->orWhere('patients.id', 'like', "%{$keyword}%");
                });
            }

            if ($request->filled('date_from')) {
                $query->whereRaw('DATE(COALESCE(checkups.checkup_date, checkups.created_at)) >= ?', [$request->date_from]);
            }

            if ($request->filled('date_to')) {
                $query->whereRaw('DATE(COALESCE(checkups.checkup_date, checkups.created_at)) <= ?', [$request->date_to]);
            }

            if ($request->filled('payment_status')) {
                $this->applyPaymentStatusFilter(
                    $query,
                    (string) $request->payment_status,
                    $pendingExpression
                );
            }

            $checkups = $query->orderBy('checkups.id', 'desc')->get();

            $doctors = DB::table('doctors')
                ->select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"))
                ->when(!$user->hasRole('admin') && !empty($user->branch_id), function ($doctorQuery) use ($user) {
                    $doctorQuery->where('branch_id', $user->branch_id);
                })
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            return view('consultations.index', [
                'checkups' => $checkups,
                'consultations' => $checkups,
                'doctors' => $doctors,
                'consultationTypes' => $this->consultationTypeOptions(),
                'paymentStatusOptions' => [
                    'fully_paid' => 'Fully Paid',
                    'partially_paid' => 'Partially Paid',
                    'unpaid' => 'Unpaid',
                ],
                'filters' => [
                    'consultation_type' => $request->input('consultation_type', ''),
                    'date_from' => $request->input('date_from', ''),
                    'date_to' => $request->input('date_to', ''),
                    'payment_status' => $request->input('payment_status', ''),
                    'doctor_id' => $request->input('doctor_id', ''),
                    'patient_search' => $request->input('patient_search', ''),
                ],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to load checkups: ' . $e->getMessage());
        }
    }

    /**
     * Show create form.
     */
    public function create(Request $request)
    {
        try {
            $patients = DB::table('patients')->select('id', 'name', 'mr', 'phone', 'branch_id')->get();
            $doctors = DB::table('doctors')
                ->select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"))
                ->get();
            $banks = DB::table('banks')->get();

            $oldType = session()->getOldInput('referred_by_type');
            $oldId = session()->getOldInput('referred_by_id');
            $oldName = session()->getOldInput('referred_by_name');

            $initialReferrer = $this->resolveReferrerOption($oldType, $oldId);
            $referredBySource = session()->getOldInput('referred_by_source')
                ?: $this->resolveReferredBySource($oldType, $oldId, $oldName);

            return view('consultations.create', compact(
                'patients',
                'doctors',
                'banks',
                'initialReferrer',
                'referredBySource'
            ))->with('consultationTypes', $this->consultationTypeOptions());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Search doctors/patients for consultation referred-by dropdown.
     */
    public function searchReferrers(Request $request)
    {
        $request->validate([
            'type' => ['required', Rule::in(['body_expert_doctor', 'body_expert_patient'])],
            'q' => 'nullable|string|max:100',
        ]);

        $type = $request->input('type');
        $keyword = trim((string) $request->input('q', ''));

        if ($type === 'body_expert_doctor') {
            $doctors = DB::table('doctors')
                ->select('id', 'first_name', 'last_name')
                ->when($keyword !== '', function ($query) use ($keyword) {
                    $query->where(function ($inner) use ($keyword) {
                        $inner->where('first_name', 'like', "%{$keyword}%")
                            ->orWhere('last_name', 'like', "%{$keyword}%");
                    });
                })
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(20)
                ->get()
                ->map(function ($doctor) {
                    $name = trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''));

                    return [
                        'id' => $doctor->id,
                        'name' => $name !== '' ? $name : 'Doctor #' . $doctor->id,
                    ];
                })
                ->values();

            return response()->json(['data' => $doctors]);
        }

        $patients = Patient::query()
            ->select('id', 'name', 'mr')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('name', 'like', "%{$keyword}%")
                        ->orWhere('mr', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($patient) {
                return [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'mr_number' => $patient->mr,
                ];
            })
            ->values();

        return response()->json(['data' => $patients]);
    }

    /**
     * Store new checkup.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'doctor_id' => 'required|exists:doctors,id',
                'consultation_type' => ['required', Rule::in($this->consultationTypeOptions())],
                'fee' => 'required|numeric|min:0',
                'paid_amount' => 'nullable|numeric|min:0',
                'payment_method' => 'nullable|string',
                'description' => 'nullable|string|max:500',
                'discount' => 'nullable|numeric|min:0|max:100',
                'referred_by_type' => ['nullable', Rule::in([
                    'body_expert_doctor',
                    'body_expert_patient',
                    'external_doctor',
                    'external_patient',
                    'social_media',
                ])],
                'referred_by_source' => ['nullable', Rule::in(['internal', 'external'])],
                'referred_by_id' => 'nullable|integer',
                'referred_by_name' => 'nullable|string|max:255',
            ]);

            $validator->after(function (ValidationValidator $validator) use ($request) {
                $this->validateReferredBySelection($request, $validator);
                $this->validateFinancialInputs(
                    (float) $request->input('fee', 0),
                    (float) $request->input('discount', 0),
                    (float) $request->input('paid_amount', 0),
                    $validator
                );
            });

            $validatedData = $validator->validate();
            $referredByData = $this->prepareReferredByData($validatedData);

            DB::beginTransaction();

            $patient = DB::table('patients')->where('id', $validatedData['patient_id'])->first();
            if (!$patient) {
                return back()->with('error', '❌ Patient not found.');
            }

            $fee = (float) ($validatedData['fee'] ?? 0);
            $discount = (float) ($validatedData['discount'] ?? 0);
            $paidAmount = (float) ($validatedData['paid_amount'] ?? 0);
            $pendingAmount = Checkup::calculatePendingAmount($fee, $discount, $paidAmount);
            $paymentMethod = $validatedData['payment_method'] ?? null;
            $bankId = $this->normalizeBankId($paymentMethod);

            $checkup = Checkup::create([
                'patient_id' => $validatedData['patient_id'],
                'doctor_id' => $validatedData['doctor_id'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'branch_id' => $patient->branch_id,
                'consultation_type' => $validatedData['consultation_type'],
                'fee' => $fee,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
                'payment_method' => $paymentMethod,
                'referred_by' => $this->mapLegacyDoctorReferral($referredByData),
                'referred_by_type' => $referredByData['referred_by_type'] ?? null,
                'referred_by_id' => $referredByData['referred_by_id'] ?? null,
                'referred_by_name' => $referredByData['referred_by_name'] ?? null,
                'description' => $validatedData['description'] ?? null,
                'discount' => $discount,
                'status' => 'completed',
            ]);

            if ($paidAmount > 0) {
                handleGeneralTransaction(
                    branch_id: (int) $patient->branch_id,
                    bank_id: $bankId,
                    patient_id: (int) $validatedData['patient_id'],
                    doctor_id: (int) $validatedData['doctor_id'],
                    type: '+',
                    amount: $paidAmount,
                    note: 'Appointment / Consultation Fee',
                    invoice_id: $checkup->id,
                    payment_type: 1,
                    entry_by: auth()->id()
                );
            }

            DB::commit();
            return redirect()->route('consultations.print', $checkup->id)->with('success', '✅ Checkup added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '❌ Error saving checkup: ' . $e->getMessage());
        }
    }

    /**
     * Edit form.
     */
    public function edit($id)
    {
        $checkup = Checkup::findOrFail($id);
        $patients = DB::table('patients')->select('id', 'name', 'mr')->get();
        $doctors = DB::table('doctors')
            ->select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"))
            ->get();
        $banks = DB::table('banks')->get();

        return view('consultations.edit', [
            'checkup' => $checkup,
            'consultation' => $checkup,
            'patients' => $patients,
            'doctors' => $doctors,
            'banks' => $banks,
            'consultationTypes' => $this->consultationTypeOptions(),
        ]);
    }

    /**
     * Update full checkup.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'consultation_type' => ['required', Rule::in($this->consultationTypeOptions())],
            'fee' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'description' => 'nullable|string|max:500',
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request) {
            $this->validateFinancialInputs(
                (float) $request->input('fee', 0),
                (float) $request->input('discount', 0),
                (float) $request->input('paid_amount', 0),
                $validator
            );
        });

        $validatedData = $validator->validate();

        try {
            DB::beginTransaction();

            $checkup = Checkup::findOrFail($id);
            $oldPaidAmount = (float) ($checkup->paid_amount ?? 0);
            $oldBankId = $this->normalizeBankId($checkup->payment_method);

            $fee = (float) $validatedData['fee'];
            $discount = (float) ($validatedData['discount'] ?? 0);
            $newPaidAmount = (float) ($validatedData['paid_amount'] ?? 0);
            $pendingAmount = Checkup::calculatePendingAmount($fee, $discount, $newPaidAmount);

            $checkup->fill([
                'patient_id' => $validatedData['patient_id'],
                'doctor_id' => $validatedData['doctor_id'],
                'updated_by' => auth()->id(),
                'consultation_type' => $validatedData['consultation_type'],
                'fee' => $fee,
                'discount' => $discount,
                'paid_amount' => $newPaidAmount,
                'pending_amount' => $pendingAmount,
                'payment_method' => $validatedData['payment_method'] ?? null,
                'description' => $validatedData['description'] ?? null,
            ]);
            $checkup->save();

            $newBankId = $this->normalizeBankId($checkup->payment_method);
            $this->logPaidAmountAdjustment(
                $checkup,
                $oldPaidAmount,
                $newPaidAmount,
                $oldBankId,
                $newBankId,
                'Consultation updated'
            );

            DB::commit();

            return redirect()->route('consultations.index')->with('success', '✅ Checkup updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', '❌ Failed to update checkup: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update only paid amount from detail page.
     */
    public function updatePaidAmount(Request $request, $id)
    {
        $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $checkup = Checkup::findOrFail($id);

            $fee = (float) ($checkup->fee ?? 0);
            $discountPercent = (float) ($checkup->discount ?? 0);
            $newPaidAmount = (float) $request->paid_amount;
            $maxPayable = max(0, $fee - ($fee * ($discountPercent / 100)));

            if ($newPaidAmount > $maxPayable) {
                return redirect()->back()
                    ->withErrors([
                        'paid_amount' => 'Paid Amount cannot exceed Total after Discount (Rs. ' . number_format($maxPayable, 2) . ').',
                    ])
                    ->withInput();
            }

            $oldPaidAmount = (float) ($checkup->paid_amount ?? 0);
            $oldBankId = $this->normalizeBankId($checkup->payment_method);

            if ($request->filled('payment_method')) {
                $checkup->payment_method = $request->payment_method;
            }

            $checkup->updated_by = auth()->id();
            $checkup->paid_amount = $newPaidAmount;
            $checkup->pending_amount = Checkup::calculatePendingAmount($fee, $discountPercent, $newPaidAmount);
            $checkup->save();

            $newBankId = $this->normalizeBankId($checkup->payment_method);
            $this->logPaidAmountAdjustment(
                $checkup,
                $oldPaidAmount,
                $newPaidAmount,
                $oldBankId,
                $newBankId,
                'Consultation paid amount updated'
            );

            DB::commit();

            return redirect()->back()->with('success', '✅ Paid amount updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', '❌ Failed to update paid amount: ' . $e->getMessage());
        }
    }

    /**
     * Delete checkup.
     */
    public function destroy($id)
    {
        DB::table('checkups')->where('id', $id)->delete();
        return redirect()->route('consultations.index')->with('success', '🗑️ Checkup deleted successfully.');
    }

    /**
     * Show detail.
     */
    public function show($id)
    {
        $pendingExpression = $this->pendingAmountExpression();

        $checkup = DB::table('checkups')
            ->join('patients', 'checkups.patient_id', '=', 'patients.id')
            ->join('doctors', 'checkups.doctor_id', '=', 'doctors.id')
            ->leftJoin('branches', 'checkups.branch_id', '=', 'branches.id')
            ->select(
                'checkups.*',
                'patients.name as patient_name',
                'patients.phone as patient_phone',
                'patients.gender',
                DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                'branches.name as branch_name',
                DB::raw("COALESCE(checkups.consultation_type, 'Appointment') as consultation_type_display"),
                DB::raw("{$pendingExpression} as pending_amount_resolved")
            )
            ->where('checkups.id', $id)
            ->first();

        if (!$checkup) {
            abort(404);
        }

        $banks = DB::table('banks')->get();

        return view('consultations.show', [
            'checkup' => $checkup,
            'consultation' => $checkup,
            'banks' => $banks,
        ]);
    }

    /**
     * Ajax: get fee by branch.
     */
    public function getCheckupFee($patientId)
    {
        $data = DB::table('patients')
            ->leftJoin('branches', 'patients.branch_id', '=', 'branches.id')
            ->where('patients.id', $patientId)
            ->select('branches.fee')
            ->first();

        $fee = $data && $data->fee ? $data->fee : 0;

        return response()->json(['fee' => $fee]);
    }

    /**
     * Patient checkup history.
     */
    public function history($patient_id)
    {
        $patient = DB::table('patients')->where('id', $patient_id)->first();
        if (!$patient) {
            abort(404, 'Patient not found.');
        }

        $pendingExpression = $this->pendingAmountExpression();

        $history = DB::table('checkups')
            ->join('doctors', 'checkups.doctor_id', '=', 'doctors.id')
            ->leftJoin('doctors as ref', 'checkups.referred_by', '=', 'ref.id')
            ->leftJoin('branches', 'checkups.branch_id', '=', 'branches.id')
            ->select(
                'checkups.*',
                DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                DB::raw("COALESCE(NULLIF(checkups.referred_by_name, ''), CONCAT(ref.first_name, ' ', ref.last_name)) as referred_by_name"),
                'branches.name as branch_name',
                DB::raw("COALESCE(checkups.consultation_type, 'Appointment') as consultation_type_display"),
                DB::raw("{$pendingExpression} as pending_amount_resolved")
            )
            ->where('checkups.patient_id', $patient_id)
            ->orderBy('checkups.id', 'desc')
            ->get();

        return view('consultations.history', [
            'history' => $history,
            'patient' => $patient,
            'consultations' => $history,
        ]);
    }

    /**
     * Print checkup slip.
     */
    public function printSlip($id)
    {
        $pendingExpression = $this->pendingAmountExpression();

        $checkup = DB::table('checkups')
            ->join('patients', 'checkups.patient_id', '=', 'patients.id')
            ->join('doctors', 'checkups.doctor_id', '=', 'doctors.id')
            ->leftJoin('doctors as ref', 'checkups.referred_by', '=', 'ref.id')
            ->leftJoin('branches', 'checkups.branch_id', '=', 'branches.id')
            ->select(
                'checkups.*',
                'checkups.referred_by_name as checkup_ref_name',
                'patients.name as patient_name',
                'patients.phone as patient_phone',
                'patients.gender',
                'patients.age as patient_age',
                'patients.mr as patient_mr',
                DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                DB::raw("CONCAT(ref.first_name, ' ', ref.last_name) as doctor_ref_name"),
                'branches.name as branch_name',
                DB::raw("COALESCE(checkups.consultation_type, 'Appointment') as consultation_type_display"),
                DB::raw("{$pendingExpression} as pending_amount_resolved")
            )
            ->where('checkups.id', $id)
            ->first();

        $branches = DB::table('branches')->get();

        if (!$checkup) {
            abort(404, 'Checkup not found.');
        }

        return view('consultations.print', [
            'checkup' => $checkup,
            'branches' => $branches,
        ]);
    }

    /**
     * Print checkup slip (custom blade).
     */
    public function printSlipCustom($id)
    {
        $pendingExpression = $this->pendingAmountExpression();

        $checkup = DB::table('checkups')
            ->join('patients', 'checkups.patient_id', '=', 'patients.id')
            ->join('doctors', 'checkups.doctor_id', '=', 'doctors.id')
            ->leftJoin('doctors as ref', 'checkups.referred_by', '=', 'ref.id')
            ->leftJoin('branches', 'checkups.branch_id', '=', 'branches.id')
            ->select(
                'checkups.*',
                'checkups.referred_by_name as checkup_ref_name',
                'patients.name as patient_name',
                'patients.phone as patient_phone',
                'patients.gender',
                'patients.age as patient_age',
                'patients.mr as patient_mr',
                DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                DB::raw("CONCAT(ref.first_name, ' ', ref.last_name) as doctor_ref_name"),
                'branches.name as branch_name',
                DB::raw("COALESCE(checkups.consultation_type, 'Appointment') as consultation_type_display"),
                DB::raw("{$pendingExpression} as pending_amount_resolved")
            )
            ->where('checkups.id', $id)
            ->first();

        $branches = DB::table('branches')->get();

        if (!$checkup) {
            abort(404, 'Checkup not found.');
        }

        return view('consultations.print_custom', [
            'checkup' => $checkup,
            'branches' => $branches,
        ]);
    }

    private function consultationTypeOptions(): array
    {
        return ['Appointment', 'Enrollment'];
    }

    private function pendingAmountExpression(): string
    {
        return "CASE WHEN checkups.pending_amount IS NOT NULL THEN checkups.pending_amount ELSE CASE WHEN (COALESCE(checkups.fee, 0) - (COALESCE(checkups.fee, 0) * (COALESCE(checkups.discount, 0) / 100)) - COALESCE(checkups.paid_amount, 0)) > 0 THEN (COALESCE(checkups.fee, 0) - (COALESCE(checkups.fee, 0) * (COALESCE(checkups.discount, 0) / 100)) - COALESCE(checkups.paid_amount, 0)) ELSE 0 END END";
    }

    private function applyPaymentStatusFilter($query, string $paymentStatus, string $pendingExpression): void
    {
        if ($paymentStatus === 'fully_paid') {
            $query->whereRaw("({$pendingExpression}) <= 0");
            return;
        }

        if ($paymentStatus === 'partially_paid') {
            $query->whereRaw("({$pendingExpression}) > 0")
                ->whereRaw('COALESCE(checkups.paid_amount, 0) > 0');
            return;
        }

        if ($paymentStatus === 'unpaid') {
            $query->whereRaw("({$pendingExpression}) > 0")
                ->whereRaw('COALESCE(checkups.paid_amount, 0) <= 0');
        }
    }

    private function validateFinancialInputs(
        float $fee,
        float $discount,
        float $paidAmount,
        ValidationValidator $validator
    ): void {
        if ($discount > 100) {
            $validator->errors()->add('discount', 'Discount cannot exceed 100%.');
        }

        $maxPayable = max(0, $fee - ($fee * ($discount / 100)));
        if ($paidAmount > $maxPayable) {
            $validator->errors()->add('paid_amount', 'Paid Amount cannot exceed Total after Discount.');
        }
    }

    private function normalizeBankId($paymentMethod): int
    {
        if ($paymentMethod === null || $paymentMethod === '') {
            return 0;
        }

        if (is_numeric($paymentMethod)) {
            return (int) $paymentMethod;
        }

        $digits = preg_replace('/[^0-9]/', '', (string) $paymentMethod);

        return $digits === '' ? 0 : (int) $digits;
    }

    private function logPaidAmountAdjustment(
        Checkup $checkup,
        float $oldPaidAmount,
        float $newPaidAmount,
        int $oldBankId,
        int $newBankId,
        string $context
    ): void {
        $delta = round($newPaidAmount - $oldPaidAmount, 2);

        if (abs($delta) < 0.01) {
            return;
        }

        $amount = abs($delta);
        $type = $delta > 0 ? '+' : '-';
        $bankId = $delta > 0 ? $newBankId : $oldBankId;

        $note = sprintf(
            '%s (Checkup #%d): paid amount changed from %.2f to %.2f',
            $context,
            $checkup->id,
            $oldPaidAmount,
            $newPaidAmount
        );

        handleGeneralTransaction(
            branch_id: (int) $checkup->branch_id,
            bank_id: $bankId,
            patient_id: (int) $checkup->patient_id,
            doctor_id: (int) $checkup->doctor_id,
            type: $type,
            amount: $amount,
            note: $note,
            invoice_id: (int) $checkup->id,
            payment_type: 1,
            entry_by: auth()->id()
        );
    }

    private function validateReferredBySelection(Request $request, ValidationValidator $validator): void
    {
        $type = $request->input('referred_by_type');
        $source = $request->input('referred_by_source');
        $referrerId = $request->input('referred_by_id');
        $referrerName = trim((string) $request->input('referred_by_name', ''));

        if (!$type) {
            return;
        }

        if (in_array($type, ['body_expert_doctor', 'body_expert_patient'], true)) {
            if (!$source) {
                $validator->errors()->add('referred_by_source', 'Please select an in-system or external referrer.');
                return;
            }

            if ($source === 'internal') {
                if (!$referrerId) {
                    $validator->errors()->add('referred_by_id', 'Please select an in-system referrer.');
                    return;
                }

                if ($type === 'body_expert_doctor' && !DB::table('doctors')->where('id', $referrerId)->exists()) {
                    $validator->errors()->add('referred_by_id', 'Selected doctor was not found.');
                }

                if ($type === 'body_expert_patient' && !Patient::where('id', $referrerId)->exists()) {
                    $validator->errors()->add('referred_by_id', 'Selected patient was not found.');
                }
            }

            if ($source === 'external' && $referrerName === '') {
                $validator->errors()->add('referred_by_name', 'Please enter the external referrer name.');
            }
        }

        if (in_array($type, ['external_doctor', 'external_patient'], true) && $referrerName === '') {
            $validator->errors()->add('referred_by_name', 'Please enter the external referrer name.');
        }

        if ($type === 'social_media' && !in_array($this->normalizeSocialMediaValue($referrerName), $this->socialMediaOptions(), true)) {
            $validator->errors()->add('referred_by_name', 'Please select a valid social media platform.');
        }
    }

    private function prepareReferredByData(array $validatedData): array
    {
        $type = $validatedData['referred_by_type'] ?? null;
        $source = $validatedData['referred_by_source'] ?? null;
        $referrerId = $validatedData['referred_by_id'] ?? null;
        $referrerName = isset($validatedData['referred_by_name'])
            ? trim((string) $validatedData['referred_by_name'])
            : null;

        if (!$type) {
            return [
                'referred_by_type' => null,
                'referred_by_id' => null,
                'referred_by_name' => null,
            ];
        }

        if ($type === 'social_media') {
            return [
                'referred_by_type' => $type,
                'referred_by_id' => null,
                'referred_by_name' => $this->normalizeSocialMediaValue($referrerName),
            ];
        }

        if (in_array($type, ['external_doctor', 'external_patient'], true)) {
            return [
                'referred_by_type' => $type,
                'referred_by_id' => null,
                'referred_by_name' => $referrerName,
            ];
        }

        if (in_array($type, ['body_expert_doctor', 'body_expert_patient'], true)) {
            if ($source === 'internal') {
                return [
                    'referred_by_type' => $type,
                    'referred_by_id' => (int) $referrerId,
                    'referred_by_name' => null,
                ];
            }

            return [
                'referred_by_type' => $type,
                'referred_by_id' => null,
                'referred_by_name' => $referrerName,
            ];
        }

        return [
            'referred_by_type' => null,
            'referred_by_id' => null,
            'referred_by_name' => null,
        ];
    }

    private function mapLegacyDoctorReferral(array $referredByData): ?int
    {
        if (($referredByData['referred_by_type'] ?? null) === 'body_expert_doctor' && !empty($referredByData['referred_by_id'])) {
            return (int) $referredByData['referred_by_id'];
        }

        return null;
    }

    private function socialMediaOptions(): array
    {
        return ['facebook', 'twitter', 'youtube', 'instagram', 'other'];
    }

    private function normalizeSocialMediaValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            return null;
        }

        return in_array($normalized, $this->socialMediaOptions(), true) ? $normalized : null;
    }

    private function resolveReferrerOption(?string $type, $referrerId): ?array
    {
        if (!$type || !$referrerId) {
            return null;
        }

        if ($type === 'body_expert_doctor') {
            $doctor = DB::table('doctors')
                ->select('id', 'first_name', 'last_name')
                ->where('id', $referrerId)
                ->first();

            if (!$doctor) {
                return null;
            }

            return [
                'id' => (int) $doctor->id,
                'text' => trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? '')),
            ];
        }

        if ($type === 'body_expert_patient') {
            $patient = Patient::query()
                ->select('id', 'name', 'mr')
                ->find($referrerId);

            if (!$patient) {
                return null;
            }

            return [
                'id' => (int) $patient->id,
                'text' => $patient->name . ($patient->mr ? ' (MR#: ' . $patient->mr . ')' : ''),
            ];
        }

        return null;
    }

    private function resolveReferredBySource(?string $type, $referrerId, ?string $referrerName): ?string
    {
        if (!in_array($type, ['body_expert_doctor', 'body_expert_patient'], true)) {
            return null;
        }

        if (!empty($referrerId)) {
            return 'internal';
        }

        if (!empty($referrerName)) {
            return 'external';
        }

        return null;
    }
}
