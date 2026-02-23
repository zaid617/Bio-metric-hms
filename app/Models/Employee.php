<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
      protected $fillable = [
        'name', 'designation', 'branch_id', 'basic_salary', 'shift', 'phone', 'joining_date'
    ];
}
