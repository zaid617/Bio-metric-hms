<?php

namespace App\Modules\Payroll\Types;

final class PayrollDeductionType
{
    public const SESSION_NUMBER_MISSING = 'SESSION_NUMBER_MISSING';
    public const WRONG_EMR_NUMBER = 'WRONG_EMR_NUMBER';
    public const TIME_MISSING = 'TIME_MISSING';
    public const WRONG_PATIENT_NAME = 'WRONG_PATIENT_NAME';
    public const ABSENT = 'ABSENT';
    public const LATE_COMING = 'LATE_COMING';
    public const ADVANCE_SALARY_DEDUCTION = 'ADVANCE_SALARY_DEDUCTION';
    public const NO_SCRUB = 'NO_SCRUB';
    public const NO_ID_CARD = 'NO_ID_CARD';
    public const LATE_UPDATE = 'LATE_UPDATE';
    public const CUSTOM = 'CUSTOM';
}
