<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TreatmentSession;
use App\Models\Doctor;

class SessionTime extends Model
{
    protected $fillable = [
        'treatment_session_id',
        'session_datetime',
        'completed_by_doctor_id',   // kis doctor ne session liya
        'work_done',                // session me kiya gaya kaam
        'is_completed',             // completed ya pending
    ];

    protected $casts = [
        'session_datetime' => 'datetime',
        'is_completed'     => 'boolean',
    ];

    // ───────────────────────────────
    // Relationships
    // ───────────────────────────────

    public function treatmentSession()
    {
        return $this->belongsTo(TreatmentSession::class, 'treatment_session_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'completed_by_doctor_id');
    }

    // ───────────────────────────────
    // Helpers
    // ───────────────────────────────

    public function markCompleted(int $doctorId, string $workDone = null): void
    {
        $this->update([
            'is_completed' => true,
            'completed_by_doctor_id' => $doctorId,
            'work_done' => $workDone,
        ]);

        // Parent session ka status refresh kar do
        $this->treatmentSession?->refreshStatus();
    }
    
}
