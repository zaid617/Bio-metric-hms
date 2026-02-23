<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    // ───────────── Table Configuration ─────────────
    protected $table = 'transactions'; // Correct table name

    protected $fillable = [
        'patient_id',        // Patient ID
        'doctor_id',       // Doctor ID
        'amount',      // Payment amount
        'type',        // '+' for income, '-' for expense
        'branch_id',        // Branch ID
        'entry_by',   // User who entered the transaction
        'Remx',        // Remark e.g. "Checkup Fee", "Treatment Session Payment"
        'payment_type',// e.g. 'checkup', 'sessions'
        'bank_id',     // Bank ID if payment_method is bank_transfer
        'invoice_id',  // Link to treatment session or checkup
        'post_cash_balance',
        'post_bank_balance',
        'post_branch_balance',
        'post_total_balance',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 👇 Accessor for human readable payment type
    public function getPaymentTypeNameAttribute()
    {
        $types = [
            1 => 'Appointment',
            2 => 'Session',
            3 => 'Expense',
            4 => 'Salary',
            5 => 'Return',
        ];

        return $types[$this->payment_type] ?? 'Unknown';
    }

    // ───────────── Relationships ─────────────

    // Patient who made the transaction
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'p_id');
    }

    // Doctor associated with the transaction
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'dr_id');
    }

    // Branch where transaction occurred
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'b_id');
    }

    // User who entered the transaction
    public function enteredByUser()
    {
        return $this->belongsTo(User::class, 'entery_by');
    }
    // Treatment Session associated with the transaction (if any)
    public function TreatmentSession()
    {
        return $this->belongsTo(TreatmentSession::class, 'invoice_id');
    }

    // ───────────── Helper Methods ─────────────

    // Check if transaction is income
    public function isIncome(): bool
    {
        return $this->type === '+';
    }

    // Check if transaction is expense
    public function isExpense(): bool
    {
        return $this->type === '-';
    }

    // Check if transaction was cash (based on Remx)
    public function isCash(): bool
    {
        return str_contains(strtolower($this->Remx), 'cash');
    }

    // Check if transaction was online (based on Remx)
    public function isOnline(): bool
    {
        return str_contains(strtolower($this->Remx), 'online');
    }

    public function bank()
{
    return $this->belongsTo(Bank::class, 'bank_id');
}

}