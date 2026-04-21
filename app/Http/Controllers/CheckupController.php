<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Checkup;
use App\Models\Patient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class CheckupController extends Controller
{
    /**
     * 1️⃣ Show all checkups (role-based)
     */
    public function index()
    {
        try {
            $user = auth()->user();

            $query = DB::table('checkups')
                ->join('patients', 'checkups.patient_id', '=', 'patients.id')
                ->join('doctors', 'checkups.doctor_id', '=', 'doctors.id')
                 ->leftJoin('doctors as ref', 'checkups.referred_by', '=', 'ref.id')
                ->leftJoin('branches', 'checkups.branch_id', '=', 'branches.id')
                ->select(
                    'checkups.*',
                    'patients.name as patient_name',
                    'patients.gender',
                    'patients.mr',
                    'patients.phone as patient_phone',
                    DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                    DB::raw("CONCAT(ref.first_name, ' ', ref.last_name) as referred_by_name"),
                    'branches.name as branch_name'
                );

            // -------------------------
            // Role-based Filtering
            // -------------------------
            if ($user->hasRole('admin')) {
                // Admin → saari checkups
            } elseif ($user->hasRole('doctor')) {
                // Doctor → sirf apni checkups
                $query->where('checkups.doctor_id', $user->id);
            } else {
                // Receptionist / Other branch-based users → sirf apni branch ke checkups
                $query->where('checkups.branch_id', $user->branch_id);
            }

            $checkups = $query->orderBy('checkups.id', 'desc')->get();

            return view('consultations.index', [
                'checkups'      => $checkups,
                'consultations' => $checkups,
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to load checkups: ' . $e->getMessage());
        }
    }

    /**
     * 2️⃣ Show create form
     */
    public function create(Request $request)
    {
        try {
            $patients = DB::table('patients')->select('id', 'name', 'mr', 'phone', 'branch_id')->get();
            $doctors  = DB::table('doctors')
                ->select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"))
                ->get();
            $banks = DB::table('banks')->get();

            $oldType = session()->getOldInput('referred_by_type');
            $oldId = session()->getOldInput('referred_by_id');
            $oldName = session()->getOldInput('referred_by_name');

            $initialReferrer = $this->resolveReferrerOption($oldType, $oldId);
            $referredBySource = session()->getOldInput('referred_by_source')
                ?: $this->resolveReferredBySource($oldType, $oldId, $oldName);

            return view('consultations.create', compact('patients', 'doctors', 'banks', 'initialReferrer', 'referredBySource'));

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
     * 3️⃣ Store new checkup
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id'     => 'required|exists:patients,id',
                'doctor_id'      => 'required|exists:doctors,id',
                'fee'            => 'required|numeric|min:0',
                'paid_amount'    => 'nullable|numeric|min:0',
                'payment_method' => 'nullable|string',
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
            });

            $validatedData = $validator->validate();
            $referredByData = $this->prepareReferredByData($validatedData);

            DB::beginTransaction();

            $patient = DB::table('patients')->where('id', $validatedData['patient_id'])->first();
            if (!$patient) {
                return back()->with('error', '❌ Patient not found.');
            }

            // Create Checkup
            $checkup = Checkup::create([
                'patient_id'     => $validatedData['patient_id'],
                'doctor_id'      => $validatedData['doctor_id'],
                'branch_id'      => $patient->branch_id,
                'fee'            => $validatedData['fee'] ?? 0,
                'paid_amount'    => $validatedData['paid_amount'] ?? 0,
                'payment_method' => $validatedData['payment_method'] ?? null,
                'referred_by' => $this->mapLegacyDoctorReferral($referredByData),
                'referred_by_type' => $referredByData['referred_by_type'] ?? null,
                'referred_by_id' => $referredByData['referred_by_id'] ?? null,
                'referred_by_name' => $referredByData['referred_by_name'] ?? null,
                'status'         => 'completed',
            ]);

            handleGeneralTransaction(
                branch_id: $patient->branch_id,
                bank_id: $validatedData['payment_method'] ?? null,
                patient_id: $validatedData['patient_id'],
                doctor_id: $validatedData['doctor_id'],
                type: '+',
                amount: $validatedData['paid_amount'] ?? 0,
                note: 'Appointment / Consultation Fee',
                invoice_id: $checkup->id,
                payment_type: 1,
                entry_by: auth()->id()
            );

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
     * 4️⃣ Edit form
     */
    public function edit($id)
    {
        $checkup  = Checkup::findOrFail($id);
        $patients = DB::table('patients')->select('id', 'name')->get();
        $doctors  = DB::table('doctors')
            ->select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"))
            ->get();

        return view('consultations.edit', [
            'checkup'       => $checkup,
            'consultation'  => $checkup,
            'patients'      => $patients,
            'doctors'       => $doctors,
        ]);
    }

    /**
     * 5️⃣ Update checkup
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'doctor_id'      => 'required|exists:doctors,id',
            'fee'            => 'required|numeric|min:0',
            'paid_amount'    => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
        ]);

        DB::table('checkups')->where('id', $id)->update([
            'patient_id'     => $request->patient_id,
            'doctor_id'      => $request->doctor_id,
            'fee'            => $request->fee,
            'paid_amount'    => $request->paid_amount ?? 0,
            'payment_method' => $request->payment_method ?? null,
            'updated_at'     => now(),
        ]);

        return redirect()->route('checkups.index')->with('success', '✅ Checkup updated successfully.');
    }

    /**
     * 6️⃣ Delete checkup
     */
    public function destroy($id)
    {
        DB::table('checkups')->where('id', $id)->delete();
        return redirect()->route('checkups.index')->with('success', '🗑️ Checkup deleted successfully.');
    }

    /**
     * 7️⃣ Show detail
     */
    public function show($id)
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
                DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                'branches.name as branch_name'
            )
            ->where('checkups.id', $id)
            ->first();

        if (!$checkup) abort(404);

        return view('consultations.show', [
            'checkup'      => $checkup,
            'consultation' => $checkup,
        ]);
    }

    /**
     * 8️⃣ Ajax: Get fee by branch
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
     * 🔟 Patient History
     */
    public function history($patient_id)
    {
        $patient = DB::table('patients')->where('id', $patient_id)->first();
        if (!$patient) abort(404, 'Patient not found.');

     $history = DB::table('checkups')
    ->join('doctors', 'checkups.doctor_id', '=', 'doctors.id')
    ->leftJoin('doctors as ref', 'checkups.referred_by', '=', 'ref.id') // <-- add this
    ->leftJoin('branches', 'checkups.branch_id', '=', 'branches.id')
    ->select(
        'checkups.*',
        DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name"),
        DB::raw("CONCAT(ref.first_name, ' ', ref.last_name) as referred_by_name"),
        'branches.name as branch_name'
    )
    ->where('checkups.patient_id', $patient_id)
    ->orderBy('checkups.id', 'desc')
    ->get();


        return view('consultations.history', [
            'history'       => $history,
            'patient'       => $patient,
            'consultations' => $history,
        ]);
    }

    /**
     * 11️⃣ Print Checkup Slip
     */
    public function printSlip($id)
    {
        $checkup = DB::table('checkups')
            ->join('patients', 'checkups.patient_id', '=', 'patients.id')
            ->join('doctors', 'checkups.doctor_id', '=', 'doctors.id')
            ->leftJoin('doctors as ref', 'checkups.referred_by', '=', 'ref.id')
            ->leftJoin('branches', 'checkups.branch_id', '=', 'branches.id')
            ->select(
                'checkups.*',
                'patients.name as patient_name',
                'patients.phone as patient_phone',
                'patients.gender',
                'patients.age as patient_age',
                'patients.mr as patient_mr',
                DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                DB::raw("CONCAT(ref.first_name, ' ', ref.last_name) as referred_by_name"),
                'branches.name as branch_name'
            )
            ->where('checkups.id', $id)
            ->first();

        $branches = DB::table('branches')->get();

        if (!$checkup) abort(404, 'Checkup not found.');

        return view('consultations.print', [
            'checkup' => $checkup,
            'branches' => $branches,
        ]);
    }

    /**
     * 12️⃣ Print Checkup Slip (Custom Blade)
     */
    public function printSlipCustom($id)
    {
        $checkup = DB::table('checkups')
            ->join('patients', 'checkups.patient_id', '=', 'patients.id')
            ->join('doctors', 'checkups.doctor_id', '=', 'doctors.id')
            ->leftJoin('doctors as ref', 'checkups.referred_by', '=', 'ref.id')
            ->leftJoin('branches', 'checkups.branch_id', '=', 'branches.id')
            ->select(
                'checkups.*',
                'patients.name as patient_name',
                'patients.phone as patient_phone',
                'patients.gender',
                'patients.age as patient_age',
                'patients.mr as patient_mr',
                DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                DB::raw("CONCAT(ref.first_name, ' ', ref.last_name) as referred_by_name"),
                'branches.name as branch_name'
            )
            ->where('checkups.id', $id)
            ->first();

        $branches = DB::table('branches')->get();

        if (!$checkup) abort(404, 'Checkup not found.');

        return view('consultations.print_custom', [
            'checkup' => $checkup,
            'branches' => $branches,
        ]);
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
