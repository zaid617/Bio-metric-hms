<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PatientController extends Controller
{
    /**
     * Display a listing of patients.
     */
    public function index(Request $request)
    {
        try {
            $query = Patient::with('branch');

            $user = auth()->user();
            $requestedBranchId = (int) $request->query('branch_id', 0);

            if ($user && !user_is_admin_like($user)) {
                $query->where('branch_id', $user->branch_id);
            } elseif ($requestedBranchId > 0) {
                $query->where('branch_id', $requestedBranchId);
            }

            if ($request->filled('search_id')) {
                $query->where('id', $request->search_id);
            }

            $patients = $query->latest()->get();
            return view('patients.indexx', compact('patients'));
        } catch (\Exception $e) {
            Log::error('Patient index error: ' . $e->getMessage());
            return back()->with('error', 'Unable to fetch patients. Please try again.');
        }
    }

    /**
     * Show the form for creating a new patient.
     */
    public function create()
    {
        try {
            $branches = Branch::select('id', 'name')->get();

            $oldType = session()->getOldInput('referred_by_type');
            $oldId = session()->getOldInput('referred_by_id');
            $oldName = session()->getOldInput('referred_by_name');

            $initialReferrer = $this->resolveReferrerOption($oldType, $oldId);
            $referredBySource = session()->getOldInput('referred_by_source')
                ?: $this->resolveReferredBySource($oldType, $oldId, $oldName);

            return view('patients.create', compact('branches', 'initialReferrer', 'referredBySource'));
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    /**
     * Search doctors/patients for referred-by dropdown.
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
     * Store a newly created patient in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'prefix' => 'required|string|in:Mr.,Ms.,Mrs.',
                'name' => 'required|string|max:255',
                'gender' => 'required|in:Male,Female,Other',
                'guardian_name' => 'required|string|max:255',
                'age' => 'required|numeric',
                'phone' => 'required|string|max:20',
                'cnic' => 'nullable|string|max:15|unique:patients,cnic',
                'address' => 'required|string|max:500',
                'branch_id' => 'required|exists:branches,id',
                'type_select' => 'nullable|string',
                'sub_select' => 'nullable|string',
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

            $validatedData = array_merge($validatedData, $referredByData);
            $validatedData['type_select'] = $validatedData['type_select'] ?? $this->mapLegacyType($referredByData['referred_by_type']);
            $validatedData['sub_select'] = $validatedData['sub_select'] ?? $this->mapLegacySubSelection($referredByData);

            unset($validatedData['referred_by_source']);

            Patient::create($validatedData);

            return redirect()->route('patients.index')
                ->with('success', 'Patient added successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            Log::error('Patient store error: ' . $e->getMessage());
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Show the form for editing a patient.
     */
    public function edit($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            $branches = Branch::all();

            $selectedType = session()->getOldInput('referred_by_type', $patient->referred_by_type);
            $selectedId = session()->hasOldInput('referred_by_id')
                ? session()->getOldInput('referred_by_id')
                : $patient->referred_by_id;
            $selectedName = session()->hasOldInput('referred_by_name')
                ? session()->getOldInput('referred_by_name')
                : $patient->referred_by_name;

            $initialReferrer = $this->resolveReferrerOption($selectedType, $selectedId);
            $referredBySource = session()->getOldInput('referred_by_source')
                ?: $this->resolveReferredBySource($selectedType, $selectedId, $selectedName);

            return view('patients.edit', compact('patient', 'branches', 'initialReferrer', 'referredBySource'));
        } catch (\Exception $e) {
            Log::error('Patient edit error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load patient edit form.');
        }
    }

    /**
     * Update the specified patient in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'prefix' => 'required|string|in:Mr.,Ms.,Mrs.',
                'name' => 'required|string|max:255',
                'gender' => 'required|in:Male,Female,Other',
                'guardian_name' => 'required|string|max:255',
                'age' => 'required|numeric',
                'phone' => 'required|string|max:20',
                'cnic' => 'nullable|string|regex:/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/|unique:patients,cnic,' . $id,
                'address' => 'required|string|max:500',
                'branch_id' => 'required|exists:branches,id',
                'type_select' => 'nullable|string',
                'sub_select' => 'nullable|string',
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

            $validatedData = array_merge($validatedData, $referredByData);
            $validatedData['type_select'] = $validatedData['type_select'] ?? $this->mapLegacyType($referredByData['referred_by_type']);
            $validatedData['sub_select'] = $validatedData['sub_select'] ?? $this->mapLegacySubSelection($referredByData);

            unset($validatedData['referred_by_source']);

            $patient = Patient::findOrFail($id);
            $patient->update([
                'prefix' => $validatedData['prefix'],
                'name' => $validatedData['name'],
                'gender' => $validatedData['gender'],
                'guardian_name' => $validatedData['guardian_name'],
                'age' => $validatedData['age'],
                'phone' => $validatedData['phone'],
                'cnic' => $validatedData['cnic'] ?? null,
                'address' => $validatedData['address'],
                'branch_id' => $validatedData['branch_id'],
                'type_select' => $validatedData['type_select'] ?? null,
                'sub_select' => $validatedData['sub_select'] ?? null,
                'referred_by_type' => $validatedData['referred_by_type'] ?? null,
                'referred_by_id' => $validatedData['referred_by_id'] ?? null,
                'referred_by_name' => $validatedData['referred_by_name'] ?? null,
            ]);

            return redirect('/patients')->with('success', 'Patient updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            Log::error('Patient update error: ' . $e->getMessage());
            return back()->with('error', 'Unable to update patient. Please try again.')->withInput();
        }
    }

    /**
     * Display the specified patient with branch and checkups.
     */
    public function show($id)
    {
        try {
            $patient = Patient::with('branch', 'checkups')->findOrFail($id);

            $financialSummary = [
                'total_fee' => 0.0,
                'total_discount' => 0.0,
                'total_paid' => 0.0,
                'total_pending' => 0.0,
            ];

            foreach ($patient->checkups as $checkup) {
                $fee = (float) ($checkup->fee ?? 0);
                $discountPercent = (float) ($checkup->discount ?? 0);
                $discountAmount = $fee * ($discountPercent / 100);
                $paid = (float) ($checkup->paid_amount ?? 0);
                $pending = \App\Models\Checkup::calculatePendingAmount($fee, $discountPercent, $paid);

                $financialSummary['total_fee'] += $fee;
                $financialSummary['total_discount'] += $discountAmount;
                $financialSummary['total_paid'] += $paid;
                $financialSummary['total_pending'] += $pending;
            }

            return view('patients.show', compact('patient', 'financialSummary'));
        } catch (\Exception $e) {
            Log::error('Patient show error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load patient details.');
        }
    }

    /**
     * Remove the specified patient from storage.
     */
    public function destroy($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            $patient->delete();

            return redirect('/patients')->with('success', 'Patient deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Patient delete error: ' . $e->getMessage());
            return back()->with('error', 'Unable to delete patient. Please try again.');
        }
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

        if ($type === 'social_media' && $referrerName !== '' && !in_array($this->normalizeSocialMediaValue($referrerName), $this->socialMediaOptions(), true)) {
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

    private function mapLegacyType(?string $type): ?string
    {
        return match ($type) {
            'body_expert_doctor', 'external_doctor' => 'doctor',
            'body_expert_patient', 'external_patient' => 'patient',
            'social_media' => 'social',
            default => null,
        };
    }

    private function mapLegacySubSelection(array $referredByData): ?string
    {
        if (($referredByData['referred_by_type'] ?? null) === 'social_media') {
            if (!empty($referredByData['referred_by_name'])) {
                return $this->formatSocialMediaLabel($referredByData['referred_by_name']);
            }

            return 'Social Media';
        }

        if (!empty($referredByData['referred_by_name'])) {
            return $referredByData['referred_by_name'];
        }

        if (!empty($referredByData['referred_by_id']) && ($referredByData['referred_by_type'] ?? null) === 'body_expert_doctor') {
            $doctor = DB::table('doctors')
                ->select('first_name', 'last_name')
                ->where('id', $referredByData['referred_by_id'])
                ->first();

            if ($doctor) {
                return trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''));
            }
        }

        if (!empty($referredByData['referred_by_id']) && ($referredByData['referred_by_type'] ?? null) === 'body_expert_patient') {
            $patient = Patient::query()
                ->select('name', 'mr')
                ->find($referredByData['referred_by_id']);

            if ($patient) {
                return $patient->name . ($patient->mr ? ' (MR#: ' . $patient->mr . ')' : '');
            }
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

    private function formatSocialMediaLabel(string $value): string
    {
        return match ($this->normalizeSocialMediaValue($value)) {
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'youtube' => 'YouTube',
            'instagram' => 'Instagram',
            'other' => 'Other',
            default => 'Social Media',
        };
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
