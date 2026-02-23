<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Checkup;

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

            return view('consultations.create', compact('patients', 'doctors', 'banks'));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * 3️⃣ Store new checkup
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'patient_id'     => 'required|exists:patients,id',
                'doctor_id'      => 'required|exists:doctors,id',
                'fee'            => 'required|numeric|min:0',
                'paid_amount'    => 'nullable|numeric|min:0',
                'payment_method' => 'nullable|string',
                'referred_by' => 'required|exists:doctors,id',
            ]);

            DB::beginTransaction();

            $patient = DB::table('patients')->where('id', $request->patient_id)->first();
            if (!$patient) {
                return back()->with('error', '❌ Patient not found.');
            }

            // Create Checkup
            $checkup = Checkup::create([
                'patient_id'     => $request->patient_id,
                'doctor_id'      => $request->doctor_id,
                'branch_id'      => $patient->branch_id,
                'fee'            => $request->fee ?? 0,
                'paid_amount'    => $request->paid_amount ?? 0,
                'payment_method' => $request->payment_method ?? null,
                'referred_by' => $request->referred_by,
                'status'         => 'completed',
            ]);

            handleGeneralTransaction(
                branch_id: $patient->branch_id,
                bank_id: $request->payment_method ?? null,
                patient_id: $request->patient_id,
                doctor_id: $request->doctor_id,
                type: '+',
                amount: $request->paid_amount ?? 0,
                note: 'Appointment / Consultation Fee',
                invoice_id: $checkup->id,
                payment_type: 1,
                entry_by: auth()->id()
            );

            DB::commit();
            return redirect()->route('consultations.print', $checkup->id)->with('success', '✅ Checkup added successfully.');

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
}
