<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Expense extends Model
{
    protected $fillable = [
        'expense_type_id',
        'amount',
        'method',
        'remarks',
        'created_by'
    ];

    public function type()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }
}
