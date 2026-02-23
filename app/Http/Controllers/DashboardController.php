<?php

namespace App\Http\Controllers\Doctors;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Checkup;
use App\Models\TreatmentSession;

class DoctorDashboardController extends Controller
{
    public function index(Request $request)
    {
        $doctorId = Auth::id(); // Logged-in doctor

        // ────────────── Appointments Pending & Completed ──────────────
        $appointmentsPending = Checkup::where('doctor_id', $doctorId)
                                      ->where('checkup_status', 0) // pending
                                      ->count();

        $appointmentsCompleted = Checkup::where('doctor_id', $doctorId)
                                        ->where('checkup_status', 1) // completed
                                        ->count();

        // ────────────── Satisfactory Sessions Pending & Completed ──────────────
        $satisfactorySessionsPending = TreatmentSession::where('doctor_id', $doctorId)
                                                        ->where('con_status', 0) // pending
                                                        ->count();

        $satisfactorySessionsCompleted = TreatmentSession::where('doctor_id', $doctorId)
                                                          ->where('con_status', 1) // completed
                                                          ->count();

        // ────────────── Today’s Sessions Pending & Completed ──────────────
        $sessionsTodayPending = TreatmentSession::where('doctor_id', $doctorId)
                                                ->where('status', 0) // pending sessions
                                                ->whereDate('session_date', now()->format('Y-m-d'))
                                                ->count();

        $sessionsTodayCompleted = TreatmentSession::where('doctor_id', $doctorId)
                                                  ->where('status', 1) // completed sessions
                                                  ->whereDate('session_date', now()->format('Y-m-d'))
                                                  ->count();

        // ────────────── Today’s Patients (Checkups) ──────────────
        $patientsTodayCount = Checkup::where('doctor_id', $doctorId)
                                     ->whereDate('checkup_date', now()->format('Y-m-d'))
                                     ->distinct('patient_id')
                                     ->count('patient_id');

        return view('doctor.dashboard', compact(
            'appointmentsPending',
            'appointmentsCompleted',
            'satisfactorySessionsPending',
            'satisfactorySessionsCompleted',
            'sessionsTodayPending',
            'sessionsTodayCompleted',
            'patientsTodayCount'
        ));
    }
}
