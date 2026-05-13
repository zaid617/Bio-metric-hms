<?php
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

if (!function_exists('user_is_admin_like')) {
    function user_is_admin_like($user = null): bool
    {
        $user = $user ?: (auth()->check() ? auth()->user() : null);

        if (!$user) {
            return false;
        }

        // Prefer Spatie role checks when available
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['admin', 'super-admin']);
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('super-admin');
        }

        // Fallback for legacy column-based roles
        return in_array($user->role ?? null, ['admin', 'super-admin'], true);
    }
}

if (!function_exists('user_branch_id')) {
    function user_branch_id($user = null): ?int
    {
        $user = $user ?: (auth()->check() ? auth()->user() : null);

        if (!$user || user_is_admin_like($user)) {
            return null;
        }

        return !empty($user->branch_id) ? (int) $user->branch_id : null;
    }
}

if (!function_exists('user_can_manage_all_branches')) {
    function user_can_manage_all_branches($user = null): bool
    {
        return user_is_admin_like($user);
    }
}

if (!function_exists('role_display_name')) {
    function role_display_name(?string $roleName): string
    {
        $roleName = (string) $roleName;

        return match ($roleName) {
            'admin' => 'CEO',
            'view-only-admin' => 'Branch Admin',
            'super-admin' => 'Super Admin',
            '' => 'N/A',
            default => ucwords(str_replace('-', ' ', $roleName)),
        };
    }
}


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
    if (user_is_admin_like()) {
        return Doctor::where('status', 'Active')->get();
    }
    return Doctor::select('id', 'first_name', 'last_name')->where('status', 'Active')->get();
}

//Transaction Function




