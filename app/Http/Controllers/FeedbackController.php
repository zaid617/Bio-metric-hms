<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TreatmentSession;

class FeedbackController extends Controller
{
    // ✅ Doctor Feedback List (branch-wise)
    public function doctorFeedbackList()
    {
        $branchId = auth()->user()->branch_id;

        // Doctor feedback fetch karte hain
        $feedbacks = DB::table('feedback')
            ->join('doctors', 'feedback.doctorid', '=', 'doctors.id')
            ->where('feedback.status', 1) // sirf active feedback
            ->select(
                'feedback.id',
                'feedback.sessionsid',
                'feedback.doctorid',
                'feedback.patientid',
                'feedback.doctor_remarks',
                'feedback.satisfaction',
                DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as doctor_name")
            )
            ->get();

        return view('feedback.doctor-list', compact('feedbacks'));
    }

    // ✅ Patient Feedback List (branch-wise)
    public function patientFeedbackList()
    {
        $branchId = auth()->user()->branch_id;

        // Patient feedback fetch karte hain
        $feedbacks = DB::table('feedback')
            ->join('patients', 'feedback.patientid', '=', 'patients.id')
            ->where('feedback.status', 1) // sirf active feedback
            ->select(
                'feedback.id',
                'feedback.sessionsid',
                'feedback.doctorid',
                'feedback.patientid',
                'feedback.patient_remarks',
                'feedback.satisfaction',
                'patients.name as patient_name'
            )
            ->get();

        return view('feedback.patient-list', compact('feedbacks'));
    }

    // ✅ Doctor Feedback Form
    public function doctorFeedbackForm($sessionId)
    {
        $branchId = auth()->user()->branch_id;

        $session = TreatmentSession::with('patient')
            ->where('branch_id', $branchId)
            ->findOrFail($sessionId);

        return view('feedback.doctor', compact('session'));
    }

    // ✅ Doctor Feedback Submit
    public function doctorFeedbackSubmit(Request $request)
    {
        $request->validate([
            'sessionsid'    => 'required|exists:treatment_sessions,id',
            'doctorid'      => 'required|exists:doctors,id',
            'patientid'     => 'required|exists:patients,id',
            'doctor_remarks'=> 'required|string',
            'satisfaction'  => 'required|numeric|min:0|max:100',
        ]);

        DB::table('feedback')->insert([
            'sessionsid'    => $request->sessionsid,
            'doctorid'      => $request->doctorid,
            'patientid'     => $request->patientid,
            'doctor_remarks'=> $request->doctor_remarks,
            'satisfaction'  => $request->satisfaction,
            'status'        => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->back()->with('success', 'Doctor feedback submitted successfully!');
    }

    // ✅ Patient Feedback Form
    public function patientFeedbackForm($sessionId)
    {
        $branchId = auth()->user()->branch_id;

        $session = TreatmentSession::with('patient')
            ->where('branch_id', $branchId)
            ->findOrFail($sessionId);

        return view('feedback.patient', compact('session'));
    }

    // ✅ Patient Feedback Submit
    public function patientFeedbackSubmit(Request $request)
    {
        $request->validate([
            'sessionsid'      => 'required|exists:treatment_sessions,id',
            'doctorid'        => 'required|exists:doctors,id',
            'patientid'       => 'required|exists:patients,id',
            'patient_remarks' => 'required|string',
            'satisfaction'    => 'required|numeric|min:0|max:100',
        ]);

        DB::table('feedback')->insert([
            'sessionsid'      => $request->sessionsid,
            'doctorid'        => $request->doctorid,
            'patientid'       => $request->patientid,
            'patient_remarks' => $request->patient_remarks,
            'satisfaction'    => $request->satisfaction,
            'status'          => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return redirect()->back()->with('success', 'Patient feedback submitted successfully!');
    }
}
