<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryIncrement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'previous_snapshot',
        'new_snapshot',
        'increment_amount',
        'increment_type',
        'effective_from',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'previous_snapshot' => 'array',
        'new_snapshot'      => 'array',
        'increment_amount'  => 'decimal:2',
        'effective_from'    => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Sum all numeric salary component values in a snapshot.
     * Mirrors Employee::getGrossSalaryAttribute() — does NOT include
     * other_allowances JSON entries to avoid double-counting with other_allowance.
     */
    public static function grossFromSnapshot(array $snapshot): float
    {
        return (float) array_sum(
            array_map(
                fn (string $field) => (float) ($snapshot[$field] ?? 0),
                Employee::SALARY_NUMERIC_FIELDS
            )
        );
    }

    public function getPreviousGrossAttribute(): float
    {
        return self::grossFromSnapshot($this->previous_snapshot ?? []);
    }

    public function getNewGrossAttribute(): float
    {
        return self::grossFromSnapshot($this->new_snapshot ?? []);
    }
}
