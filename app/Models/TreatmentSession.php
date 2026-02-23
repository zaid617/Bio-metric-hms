<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Checkup, Doctor, Patient, SessionTime, SessionInstallment, TreatmentSessionEntry};

class TreatmentSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkup_id',
        'branch_id',
        'doctor_id',
        'patient_id',
        'session_date',
        'session_fee',
        'session_count',
        'session_number',   // DB column ensure fillable
        'status',
        'payment_status',
        'paid_amount',
        'dues_amount',
        'diagnosis',
        'note',
        'ss_dr_id',
        'con_status',


    ];

    protected $casts = [
        'session_date'  => 'datetime',
        'session_fee'   => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'dues_amount'   => 'decimal:2',
    ];

    // ───────────────────────────────
    // Relationships
    // ───────────────────────────────

    public function checkup()
    {
        return $this->belongsTo(Checkup::class, 'checkup_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function sessionTimes()
    {
        return $this->hasMany(SessionTime::class, 'treatment_session_id')
                    ->orderBy('session_datetime', 'asc');
    }
    // Transactions related to this treatment session
   public function transactions()
    {
        return $this->hasMany(Transaction::class, 'invoice_id');
    }

    public function installments()
    {
        return $this->hasMany(SessionInstallment::class, 'session_id', 'id');
    }

    public function entries()
    {
        return $this->hasMany(TreatmentSessionEntry::class, 'treatment_session_id');
    }

    // ───────────────────────────────
    // Payment Helpers
    // ───────────────────────────────

    public function totalPaid(): float
    {
        return (float) $this->installments->sum('amount');
    }

    public function remainingAmount(): float
    {
        $remaining = (float) $this->session_fee - $this->totalPaid();
        return $remaining > 0 ? $remaining : 0.0;
    }

    public function perSessionFee(): float
    {
        $count = $this->sessionTimes->count();
        return $count > 0 ? round((float) $this->session_fee / $count, 2) : 0.0;
    }

    // ───────────────────────────────
    // Status & Progress Helpers
    // ───────────────────────────────

    /**
     * Refresh parent status based on child sessions
     */
    public function refreshStatus(): void
    {
        $total = $this->sessionTimes()->count();
        $completed = $this->sessionTimes()->where('is_completed', true)->count();

        if ($total === 0) {
            $this->status = '1';
        } elseif ($completed === 0) {
            $this->status = '1';
        } elseif ($completed < $total) {
            $this->status = '1';
        } else {
            $this->status = '2';
        }

        $this->save();
    }

    /**
     * Completed sessions count
     */
    public function completedSessionsCount(): int
    {
        return $this->sessionTimes->where('is_completed', true)->count();
    }

    /**
     * Pending sessions count
     */
    public function pendingSessionsCount(): int
    {
        return $this->sessionTimes->where('is_completed', false)->count();
    }

    /**
     * Upcoming sessions (future & pending)
     */
    public function upcomingSessions()
    {
        return $this->sessionTimes
            ->filter(fn ($session) => !$session->is_completed && $session->session_datetime->isFuture());
    }

    /**
     * Missed sessions (past & not completed)
     */
    public function missedSessions()
    {
        return $this->sessionTimes
            ->filter(fn ($session) => !$session->is_completed && $session->session_datetime->isPast());
    }
}
