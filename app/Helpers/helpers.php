<?php
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


function doctor_get_name($id)
{
    $doctor = Doctor::find($id);
     return $doctor ? $doctor->name : 'Unknown Doctor';
}

function patient_get_name($id)
{
    $patient =Patient::find($id);
    return $patient ? $patient->name : 'Unknown Patient';
}

function patient_get_mr($id)
{
    $patient =Patient::find($id);
    return $patient ? $patient->mr : 'Unknown MR';
}

function bank_get_name($id)
{
    if (!$id || $id == '0') {
        return 'Cash';
    } else {
        $bank = DB::table('banks')->where('id', $id)->first();
        $name =  $bank->bank_name . ' | (' . $bank->account_no . ') | ' . $bank->account_title;
        return $name ? $name : 'Unknown Bank';
    }
}

//

function format_date($date)
{
    return Carbon::parse($date)->format('d/m/Y');
}

function format_time($date)
{
    return Carbon::parse($date)->format('h:i A');
}
function format_datetime($date)
{
    return Carbon::parse($date)->format('d/m/Y - h:i A');
}

function get_doctors()
{
    if (auth()->user()->role == 'admin') {
        return Doctor::where('status', 'Active')->get();
    }
    return Doctor::select('id', 'first_name', 'last_name')->where('status', 'Active')->get();
}

//Transaction Function




