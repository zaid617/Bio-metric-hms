<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SessionTime;
use App\Models\TreatmentSession;

class SessionTimeController extends Controller
{
    /**
     * Mark a session as completed by doctor
     */
    public function markCompleted(Request $request, $id)
    {
        try {
            $request->validate([
                'doctor_id' => 'required|exists:doctors,id',
                'work_done' => 'nullable|string|max:1000',
            ]);

            $sessionTime = SessionTime::findOrFail($id);

            // Agar already completed hai to dobara na kare
            if ($sessionTime->is_completed) {
                return redirect()->back()->with('info', 'This session is already marked as completed.');
            }

            $sessionTime->update([
                'is_completed'            => true,
                'completed_by_doctor_id'  => $request->doctor_id,
                'work_done'               => $request->work_done,
            ]);

            // Parent TreatmentSession ka status update
            $sessionTime->treatmentSession->refreshStatus();

            return redirect()->back()->with('success', 'Session marked as completed successfully!');
        } catch (\Exception $e) {
            \Log::error('Session markCompleted error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to mark session as completed.');
        }
    }

    /**
     * Delete a session time entry
     */
    public function destroy($id)
    {
        try {
            $sessionTime = SessionTime::findOrFail($id);
            $treatmentSession = $sessionTime->treatmentSession;

            // Agar session already completed hai to delete na karne dein
            if ($sessionTime->is_completed) {
                return redirect()->back()->with('error', 'Completed sessions cannot be deleted.');
            }

            $sessionTime->delete();

            // Parent ka status refresh karo
            $treatmentSession->refreshStatus();

            return redirect()->back()->with('success', 'Session deleted successfully!');
        } catch (\Exception $e) {
            \Log::error('Session destroy error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to delete session.');
        }
    }

    // Update Section Completed Status
    public function updateSectionCompleted(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required',
                'doctor_id' => 'required|exists:doctors,id',
                'status' => 'required|in:0,1',
            ]);

            $session = SessionTime::findOrFail($request->session_id);
            $session->is_completed = $request->status;
            $session->completed_by_doctor_id = $request->doctor_id;
            $session->updated_at = now();
            $session->save();

            // Parent TreatmentSession ko id se update karein
            $treatmentSession = TreatmentSession::findOrFail($session->treatment_session_id);
            $treatmentSession->refreshStatus();

            return redirect()->back()->with('success', '✅ Session marked as completed successfully.');
        } catch (\Exception $e) {
            echo $e->getMessage(); exit; // Debugging line
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
