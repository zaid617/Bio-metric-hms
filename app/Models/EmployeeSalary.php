<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalary extends Model
{
        protected $fillable = [
        'employee_id', 'month', 'basic_salary', 'allowances',
        'deductions', 'bonuses', 'net_salary', 'payment_status', 'paid_on'
    ];

}
