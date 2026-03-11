<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $table = 'payroll_settings';

    protected $fillable = [
        'default_shift_hours',
        'overtime_multiplier',
        'work_on_saturday',
        'shift_start',
        'late_grace_minutes',
        'treatment_extension_commission',
        'assessment_incentive',
        'personal_patient_commission',
        'satisfactory_session_amount',
        'satisfaction_threshold',
        'satisfaction_bonus_per_feedback',
        'reference_bonus_per_patient',
        'punctuality_amount',
        'absent_per_day',
        'late_per_day',
    ];

    protected $casts = [
        'work_on_saturday'                => 'boolean',
        'default_shift_hours'             => 'float',
        'overtime_multiplier'             => 'float',
        'late_grace_minutes'              => 'integer',
        'treatment_extension_commission'  => 'float',
        'assessment_incentive'            => 'float',
        'personal_patient_commission'     => 'float',
        'satisfactory_session_amount'     => 'float',
        'satisfaction_threshold'          => 'integer',
        'satisfaction_bonus_per_feedback' => 'float',
        'reference_bonus_per_patient'     => 'float',
        'punctuality_amount'              => 'float',
        'absent_per_day'                  => 'float',
        'late_per_day'                    => 'float',
    ];

    /**
     * Always work with the single settings row.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'default_shift_hours'             => 8,
            'overtime_multiplier'             => 1.5,
            'work_on_saturday'                => true,
            'shift_start'                     => '09:00',
            'late_grace_minutes'              => 15,
            'treatment_extension_commission'  => 0.10,
            'assessment_incentive'            => 0.05,
            'personal_patient_commission'     => 0.20,
            'satisfactory_session_amount'     => 300,
            'satisfaction_threshold'          => 90,
            'satisfaction_bonus_per_feedback' => 200,
            'reference_bonus_per_patient'     => 500,
            'punctuality_amount'              => 2000,
            'absent_per_day'                  => 500,
            'late_per_day'                    => 200,
        ]);
    }

    /**
     * Return all settings as a flat array matching the config/payroll.php structure.
     */
    public function toConfigArray(): array
    {
        return [
            'default_shift_hours' => $this->default_shift_hours,
            'overtime_multiplier' => $this->overtime_multiplier,
            'work_on_saturday'    => $this->work_on_saturday,
            'shift_start'         => $this->shift_start,
            'late_grace_minutes'  => $this->late_grace_minutes,
            'rates' => [
                'treatment_extension_commission' => $this->treatment_extension_commission,
                'assessment_incentive'           => $this->assessment_incentive,
                'personal_patient_commission'    => $this->personal_patient_commission,
            ],
            'bonuses' => [
                'satisfactory_session_amount'     => $this->satisfactory_session_amount,
                'satisfaction_threshold'          => $this->satisfaction_threshold,
                'satisfaction_bonus_per_feedback' => $this->satisfaction_bonus_per_feedback,
                'reference_bonus_per_patient'     => $this->reference_bonus_per_patient,
            ],
            'awards' => [
                'punctuality_amount' => $this->punctuality_amount,
            ],
            'deductions' => [
                'absent_per_day' => $this->absent_per_day,
                'late_per_day'   => $this->late_per_day,
            ],
        ];
    }
}
