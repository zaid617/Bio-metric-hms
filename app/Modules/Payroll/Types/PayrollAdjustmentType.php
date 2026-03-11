<?php

namespace App\Modules\Payroll\Types;

final class PayrollAdjustmentType
{
    public const EARNING = 'earning';
    public const DEDUCTION = 'deduction';
    public const AWARD = 'award';

    public const ALL = [
        self::EARNING,
        self::DEDUCTION,
        self::AWARD,
    ];
}
