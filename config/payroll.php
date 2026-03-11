<?php

return [
    'default_shift_hours' => 8,
    'overtime_multiplier' => 1.5,

    // Shift schedule
    // Set work_on_saturday = true for Mon–Sat, false for Mon–Fri
    'work_on_saturday'   => true,
    'shift_start'        => '09:00',  // HH:MM — official shift start time
    'late_grace_minutes' => 15,       // minutes of grace before marking late

    'rates' => [
        'treatment_extension_commission' => 0.10,
        'assessment_incentive' => 0.05,
        'personal_patient_commission' => 0.20,
    ],

    'bonuses' => [
        'satisfactory_session_amount' => 300,
        'satisfaction_threshold' => 90,
        'satisfaction_bonus_per_feedback' => 200,
        'reference_bonus_per_patient' => 500,
    ],

    'awards' => [
        'punctuality_amount' => 2000,
    ],

    'deductions' => [
        'absent_per_day' => 500,
        'late_per_day' => 200,
    ],
];
