<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TreatmentSession;
use App\Models\SessionTime;

class SessionController extends Controller
{
    // Show list of treatment sessions with related data
    public function index(Request $request)
    {
        $query = TreatmentSession::with(['installments', 'sessionTimes', 'patient', 'doctor']);

        $user = auth()->user();
        $requestedBranchId = (int) $request->query('branch_id', 0);

        if ($user && !user_is_admin_like($user)) {
            if (!empty($user->branch_id)) {
                $query->where('branch_id', (int) $user->branch_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($requestedBranchId > 0) {
            $query->where('branch_id', $requestedBranchId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        // Eager load related models: installments, sessionTimes, patient, doctor
        $treatmentSessions = $query->get();

        // Total count of all session times (optional, agar view me chahiye)
        $totalSessionTimes = SessionTime::count();

        // Return the view with the data
        return view('sessions.index', compact('treatmentSessions', 'totalSessionTimes'));
    }
}
