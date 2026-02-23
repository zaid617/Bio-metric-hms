<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Checkup;
use App\Models\SessionTime;
use App\Models\TreatmentSession;
use App\Models\SessionInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Patient;
use App\Models\Bank;
use Illuminate\Support\Facades\Auth;

class TreatmentSessionController extends Controller
{
    //Doctor Consultation Index
    public function index($status)
    {
        try {
            $sessions = TreatmentSession::with(['doctor', 'patient', 'sessionTimes', 'installments', 'checkup'])
                ->where('con_status', $status)
                ->orderByDesc('created_at')
                ->get();

            $doctors = Doctor::all();

            return view('treatment_sessions.index', compact('sessions', 'doctors'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to load sessions: ' . $e->getMessage());
        }
    }



    public function create()
    {
        try {
            // ✅ FIX: "date" column hata ke created_at use kiya
            $checkups = Checkup::with('patient', 'doctor')->orderBy('created_at', 'desc')->get();
            $doctors  = Doctor::all();
            $patients = $checkups->pluck('patient')->unique('id');

            return view('treatment_sessions.create', compact('checkups', 'doctors', 'patients'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to load create form: ' . $e->getMessage());
        }
    }

public function store(Request $request)
{
    try {

        // ✅ VALIDATION
        $request->validate([
            'checkup_id' => 'required|exists:checkups,id',
            'doctor_id'  => 'required|exists:doctors,id',
            'diagnosis'  => 'nullable|string|max:255',
            'note'       => 'nullable|string',
            'ss_dr'      => 'nullable|exists:doctors,id',
        ]);

        DB::beginTransaction();

        // ✅ FETCH CHECKUP
        $checkup = Checkup::with('doctor')->findOrFail($request->checkup_id);

        // ✅ SATISFACTORY SESSION LOGIC
        // ss_toggle checked = NOT satisfactory → con_status = 0
        // ss_toggle not checked = satisfactory → con_status = 1
        $con_status = $request->has('ss_toggle') ? 0 : 1;

        // ✅ CREATE TREATMENT SESSION
        $session = TreatmentSession::create([
            'patient_id'  => $checkup->patient_id,
            'branch_id'   => $checkup->doctor->branch_id ?? 1,
            'checkup_id'  => $checkup->id,
            'doctor_id'   => $request->doctor_id,
            'ss_dr_id'    => $request->ss_dr ?? null,
            'diagnosis'   => $request->diagnosis,
            'note'        => $request->note,
            'con_status'  => $con_status,
            'session_fee' => 0,
        ]);

        // ✅ MARK CHECKUP COMPLETED
        $checkup->update([
            'checkup_status' => 1
        ]);

        DB::commit();

        // ✅ SAFE REDIRECT (NO LOGOUT ISSUE)
       if (auth('doctor')->check()) {
    return redirect()->route('doctor.consultations.index', ['status' => 0])
        ->with('success', '✅ Treatment session saved successfully.');
}


        return redirect()->route('doctor-consultations.index', 0)
            ->with('success', '✅ Treatment session saved successfully.');

    } catch (\Exception $e) {

        DB::rollBack();

        return redirect()->back()
            ->withInput()
            ->with('error', '❌ Error: ' . $e->getMessage());
    }
}

    public function edit($id)
    {
        try {
            $session = TreatmentSession::with(['doctor', 'patient', 'sessionTimes', 'installments', 'checkup'])
                ->findOrFail($id);

            $doctors  = Doctor::all();
            // ✅ FIX: "date" column hata ke created_at use kiya
            $checkups = Checkup::with('patient', 'doctor')->orderBy('created_at', 'desc')->get();
            $patients = $checkups->pluck('patient')->unique('id');

            return view('treatment_sessions.edit', compact('session', 'doctors', 'checkups', 'patients'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to load edit form: ' . $e->getMessage());
        }
    }





    //Enrollment update
    public function enrollmentUpdate(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'session_fee'     => 'required|numeric|min:0',
                'paid_amount'     => 'required|numeric|min:0',
                'session_date'  => $request->sessions[0]['date'] ?? now()->toDateString(),
                'payment_method' => 'nullable|string|max:100',

            ]);


            //treatment session
            $session = TreatmentSession::findOrFail($id);
            TreatmentSession::where('id', $id)->update([
                'session_fee'   => $request->session_fee,
                'session_count' => $request->session_count,
                'paid_amount'   => $request->paid_amount,
                'dues_amount'   => $request->dues_amount,
                'session_date'  => $request->session_date,
                'enrollment_status' => 1,
            ]);

            //Add session times
            if ($request->has('sessions')) {
                foreach ($request->sessions as $time) {
                    if (!empty($time['date']) && !empty($time['time'])) {
                        SessionTime::create([
                            'treatment_session_id' => $session->id,
                            'session_datetime'     => $time['date'].' '.$time['time'],
                        ]);
                    }
                }
            }

            if ($request->paid_amount > 0) {
                handleGeneralTransaction(
                branch_id: $session->branch_id,
                bank_id: $request->payment_method,
                patient_id: $session->patient_id,
                doctor_id: $session->doctor_id,
                type: '+',
                amount: $request->paid_amount ?? 0,
                note: 'Session Payment',
                invoice_id: $session->id,
                payment_type: 2,
                entry_by: auth()->id()
            );

            }
            DB::commit();
            return redirect()->route('enrollments', ['status' => 1])
                ->with('success', '✅ Enrollment status updated successfully.');
        } catch (\Exception $e) {
            DB::Rollback();
            return redirect()->back()->with('error', '❌ Failed to update enrollment status: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
              'checkup_id'      => 'required|exists:checkups,id',
    'doctor_id'       => 'required|exists:doctors,id',
    'ss_dr'           => $request->input('satisfactory_check') ? 'required|exists:doctors,id' : 'nullable|exists:doctors,id',
    'session_fee'     => 'required|numeric|min:0',
    'paid_amount'     => 'required|numeric|min:0',
    'diagnosis'       => 'nullable|string|max:255',
    'note'            => 'nullable|string',
    'sessions'        => 'nullable|array',
    'sessions.*.date' => 'nullable|date',
    'sessions.*.time' => 'nullable|date_format:H:i',
            ]);

            $session = TreatmentSession::findOrFail($id);
            $checkup = Checkup::findOrFail($request->checkup_id);

            $sessionCount = $request->has('sessions') ? count($request->sessions) : $session->session_count;
            $totalFee     = $request->session_fee * $sessionCount;
            $paidAmount   = (float) $request->paid_amount;
            $duesAmount   = $totalFee - $paidAmount;

            $session->update([
                'patient_id'    => $checkup->patient_id,
                'branch_id'     => $checkup->doctor->branch_id ?? 1,
                'checkup_id'    => $request->checkup_id,
                'doctor_id'     => $request->doctor_id,
                'session_fee'   => $request->session_fee,
                'session_count' => $sessionCount,
                'paid_amount'   => $paidAmount,
                'dues_amount'   => $duesAmount,
                'diagnosis'     => $request->diagnosis,
                'note'          => $request->note,
                'session_date'  => $request->sessions[0]['date'] ?? $session->session_date,
                'status'        => 1, // Mark as ongoing
            ]);

            // Update session times
            if ($request->has('sessions')) {
                $session->sessionTimes()->delete();
                foreach ($request->sessions as $time) {
                    if (!empty($time['date']) && !empty($time['time'])) {
                        SessionTime::create([
                            'treatment_session_id' => $session->id,
                            'session_datetime'     => $time['date'].' '.$time['time'],
                        ]);
                    }
                }
            }

            // Update installments
            $session->installments()->delete();
            if ($paidAmount > 0) {
                SessionInstallment::create([
                    'session_id'     => $session->id,
                    'amount'         => $paidAmount,
                    'payment_date'   => now(),
                    'payment_method' => 'cash',
                ]);
            }

            // Update transaction
            DB::table('transactions')->where([
                ['p_id', '=', $session->patient_id],
                ['dr_id', '=', $request->doctor_id],
                ['Remx', '=', 'Treatment Session Payment']
            ])->update([
                'amount'     => $paidAmount,
                'b_id'       => $checkup->doctor->branch_id ?? 1,
                'updated_at' => now(),
            ]);

            return redirect()->route('treatment-sessions.index')
                ->with('success', '✅ Treatment session updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to update session: ' . $e->getMessage());
        }
    }

    public function markCompleted(Request $request, $id)
    {
        try {
            $request->validate([
                'doctor_id' => 'required|exists:doctors,id',
                'work_done' => 'nullable|string|max:255',
            ]);

            $sessionTime = SessionTime::findOrFail($id);

            $sessionTime->update([
                'is_completed'           => true,
                'completed_by_doctor_id' => $request->doctor_id,
                'work_done'              => $request->work_done ?? null,
            ]);

            $sessionTime->treatmentSession->refreshStatus();

            return redirect()->back()->with('success', '✅ Session marked completed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to mark session as completed: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $session = TreatmentSession::findOrFail($id);

            $session->sessionTimes()->delete();
            $session->installments()->delete();

            DB::table('transactions')->where([
                ['p_id', '=', $session->patient_id],
                ['dr_id', '=', $session->doctor_id],
                ['Remx', '=', 'Treatment Session Payment']
            ])->delete();

            $session->delete();

            return redirect()->route('treatment-sessions.index')
                ->with('success', '🗑️ Treatment session and transaction deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to delete session: ' . $e->getMessage());
        }
    }

    // Optional methods for ➕ icon session entry
    public function addEntryForm($session_id)
    {
        $session = TreatmentSession::findOrFail($session_id);
        return view('treatment_sessions.add_entry', compact('session'));
    }

    public function storeEntry(Request $request, $session_id)
    {
        $request->validate([
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ]);

        SessionTime::create([
            'treatment_session_id' => $session_id,
            'session_datetime'     => $request->date.' '.$request->time,
        ]);

        return redirect()->back()->with('success', '✅ Session entry added successfully.');

    }
    // 🔹 Show ongoing sessions for a patient
public function showOngoingSessions($session_id)
{
    try {
        // 🔹 Fetch ongoing sessions
        $ongoingSessions = TreatmentSession::where('id', $session_id)->first();

        $Checkup = Checkup::where('id', $ongoingSessions->checkup_id)->update(['checkup_status' => 1]);
        // 🔹 Get patient info for form heading
        $patient = Patient::find($ongoingSessions->patient_id);
        $banks = Bank::all();

        return view('treatment_sessions.create_sessions', compact('ongoingSessions', 'patient', 'Checkup', 'banks'));
    } catch (\Exception $e) {
        return redirect()->back()->with('error', '❌ Failed to load ongoing sessions: ' . $e->getMessage());
    }
}
//------------------------------------------Enrollment list---------------------------------------------
public function show($status)
{
    try {
        $enrollments = $sessions = TreatmentSession::with(['doctor', 'patient', 'checkup'])
                ->where('con_status', 1)
                ->orderByDesc('created_at')
                ->get();


        return view('treatment_sessions.enrollments', compact('enrollments'));
    } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to load enrollments: ' . $e->getMessage());
        }
    }

    // Show OngoingSessionsOnly based on status (1 = Ongoing, 2 = Completed, 3 = Cancelled)
   public function OngoingSessionsOnly($status)
{
    try {
        $doctorId = Auth::id(); // Login doctor ka ID

        $sessions = TreatmentSession::with(['doctor', 'patient', 'checkup'])
            ->withCount([
                'sessionTimes as pending_count' => function ($query) {
                    $query->where('is_completed', 0);
                },
                'sessionTimes as completed_count' => function ($query) {
                    $query->where('is_completed', 1);
                },
            ])
            ->where('status', $status)
            ->where('enrollment_status', 1)
            ->where('doctor_id', $doctorId) // <-- Yahan filter add kiya
            ->orderByDesc('created_at')
            ->get();
  //echo '<pre>'; print_r($sessions->toArray()); echo '</pre>'; exit; // Debugging line
                return view('treatment_sessions.sessions', compact('sessions'));
        } catch (\Exception $e) {
                return redirect()->back()->with('error', '❌ Failed to load enrollments: ' . $e->getMessage());
            }
        }

        // Show enrollments based on status (1 = Ongoing, 2 = Completed, 3 = Cancelled)


    public function showEnrollments($status)
    {
        try {
                $enrollments = $sessions = TreatmentSession::with(['doctor', 'patient', 'checkup'])
                    ->where('con_status', 1)
                    ->where('enrollment_status', $status)
                    ->orderByDesc('created_at')
                    ->get();
                    //echo '<pre>'; print_r($enrollments->toArray()); echo '</pre>'; exit; // Debugging line
                return view('treatment_sessions.enrollments', compact('enrollments'));
        } catch (\Exception $e) {
                return redirect()->back()->with('error', '❌ Failed to load enrollments: ' . $e->getMessage());
            }
        }

    // Update Satisfactory session status
    public function updateStatus(Request $request)
    {
        try {
            $request->validate([
                'con_status' => 'required|in:0,1',
                'diagnosis'  => 'nullable|string|max:255',
                'note'       => 'nullable|string',
                'session_id' => 'required|exists:treatment_sessions,id',
            ]);

          $session = TreatmentSession::findOrFail($request->session_id);

// 🔹 Automatic Completed Logic
if (empty($session->ss_dr_id) || $session->sessionTimes->where('is_completed', false)->count() === 0) {
    $session->con_status = 1; // Completed automatically
} else {
    $session->con_status = $request->con_status; // Manual select
}

$session->diagnosis = $request->diagnosis;
$session->note      = $request->note;
$session->save();


            return redirect()->route('doctor-consultations.index', 0)->with('success', '✅ Consultation status updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to update consultation status: ' . $e->getMessage());
        }
    }

    // View Satisfactory session status
    public function viewssStatus($id)
    {
        try {
            $session = TreatmentSession::where('con_status', 0)
                ->where('id', $id)
                ->orderByDesc('created_at')
                ->first();

               // echo '<pre>'; print_r($session->toArray()); echo '</pre>'; exit; // Debugging line


           return view('treatment_sessions.ss_update', compact('session'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to load consultation status: ' . $e->getMessage());
        }
    }

    // Session Details
    public function sessionDetails($id)
    {
        try {
            $session = TreatmentSession::with(['patient', 'sessionTimes'])
                ->findOrFail($id);

                //echo '<pre>'; print_r($session->toArray()); echo '</pre>'; exit; // Debugging line

            return view('treatment_sessions.session_detail', compact('session'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Failed to load session details: ' . $e->getMessage());
        }
    }



}
