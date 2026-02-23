<?php

namespace App\Http\Controllers\Doctors;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Checkup;
use App\Models\TreatmentSession;
use Carbon\Carbon;

class DoctorDashboardController extends Controller
{
    public function index()
    {
       $doctor = auth()->user(); // ye Doctor model se fetch karega
 // Logged-in doctor
        $today = Carbon::today()->toDateString();

        // ✅ Appointments
        $appointmentsPending = Checkup::where('doctor_id', $doctor->id)
            ->where('checkup_status', 0) // 0 = pending
            ->count();

        $appointmentsCompleted = Checkup::where('doctor_id', $doctor->id)
            ->where('checkup_status', 1) // 1 = completed
            ->count();

        // ✅ Satisfactory Sessions
        $satisfactorySessionsPending = TreatmentSession::where('doctor_id', $doctor->id)
            ->where('con_status', 0)
            ->count();

        $satisfactorySessionsCompleted = TreatmentSession::where('doctor_id', $doctor->id)
            ->where('con_status', 1)
            ->count();

        // ✅ Today's Sessions
        $sessionsTodayPending = TreatmentSession::where('doctor_id', $doctor->id)
            ->whereDate('created_at', $today)
            ->where('con_status', 0)
            ->count();

        $sessionsTodayCompleted = TreatmentSession::where('doctor_id', $doctor->id)
            ->whereDate('created_at', $today)
            ->where('con_status', 1)
            ->count();

        // ✅ Today's unique patients
        $patientsTodayCount = Checkup::where('doctor_id', $doctor->id)
            ->whereDate('created_at', $today)
            ->distinct('patient_id')
            ->count('patient_id');

        // Return to Blade with only defined variables
        return view('doctors.dashboard', compact(
            'doctor',
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
