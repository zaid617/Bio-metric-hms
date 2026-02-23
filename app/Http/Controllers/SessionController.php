<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TreatmentSession;
use App\Models\SessionTime;

class SessionController extends Controller
{
    // Show list of treatment sessions with related data
    public function index()
    {
        // Eager load related models: installments, sessionTimes, patient, doctor
        $treatmentSessions = TreatmentSession::with(['installments', 'sessionTimes', 'patient', 'doctor'])->get();

        // Total count of all session times (optional, agar view me chahiye)
        $totalSessionTimes = SessionTime::count();

        // Return the view with the data
        return view('sessions.index', compact('treatmentSessions', 'totalSessionTimes'));
    }
}
