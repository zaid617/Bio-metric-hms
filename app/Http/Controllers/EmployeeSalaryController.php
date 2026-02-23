<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EmployeeSalaryController extends Controller
{
    // 🔹 Show all salaries
    public function index()
    {
        try {
            $salaries = DB::table('employee_salaries')
                ->join('employees', 'employee_salaries.employee_id', '=', 'employees.id')
                ->select('employee_salaries.*', 'employees.name as employee_name')
                ->get();

            $totalToPay = $salaries->where('payment_status', 'Pending')->sum('net_salary');

            return view('salaries.index', compact('salaries', 'totalToPay'));
        } catch (\Exception $e) {
            \Log::error('Salary index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to fetch salaries.');
        }
    }

    // 🔹 Show create form
    public function create()
    {
        try {
            $employees = DB::table('employees')->get();
            return view('salaries.create', compact('employees'));
        } catch (\Exception $e) {
            \Log::error('Salary create error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to load salary form.');
        }
    }

    // 🔹 Store salary
    public function store(Request $request)
    {
        try {
            $netSalary = $request->basic_salary + $request->bonuses + $request->allowances - $request->deductions;

            $formattedDate = Carbon::createFromFormat('Y-m-d', $request->month)->format('Y-m-d');

            DB::table('employee_salaries')->insert([
                'employee_id'    => $request->employee_id,
                'month'          => $formattedDate,
                'basic_salary'   => $request->basic_salary,
                'allowances'     => $request->allowances,
                'deductions'     => $request->deductions,
                'bonuses'        => $request->bonuses,
                'net_salary'     => $netSalary,
                'payment_status' => 'Pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return redirect('salaries')->with('success', 'Salary added successfully.');
        } catch (\Exception $e) {
            \Log::error('Salary store error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to add salary.');
        }
    }

    // 🔹 Mark as Paid (Old basic method - optional if using modal only)
    public function markAsPaid($id)
    {
        try {
            DB::table('employee_salaries')->where('id', $id)->update([
                'payment_status' => 'Paid',
                'paid_on'        => now(),
                'updated_at'     => now(),
            ]);

            return redirect('salaries')->with('success', 'Salary marked as paid.');
        } catch (\Exception $e) {
            \Log::error('Salary markAsPaid error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to mark salary as paid.');
        }
    }

    // ✅ 🔸 NEW METHOD: Mark as Paid with Bonus/Deductions from Modal
    public function markPaidWithAdjustment(Request $request)
    {
        try {
            $request->validate([
                'salary_id'  => 'required|exists:employee_salaries,id',
                'bonuses'    => 'nullable|numeric',
                'deductions' => 'nullable|numeric',
            ]);

            $salaryId  = $request->input('salary_id');
            $bonus     = $request->input('bonuses') ?? 0;
            $deduction = $request->input('deductions') ?? 0;

            $salary = DB::table('employee_salaries')->where('id', $salaryId)->first();

            if (!$salary || $salary->payment_status === 'Paid') {
                return redirect()->back()->with('error', 'Salary already paid or not found.');
            }

            $newNetSalary = ($salary->basic_salary + $salary->allowances + $bonus) - $deduction;

            DB::table('employee_salaries')->where('id', $salaryId)->update([
                'bonuses'        => $bonus,
                'deductions'     => $deduction,
                'net_salary'     => $newNetSalary,
                'payment_status' => 'Paid',
                'paid_on'        => now(),
                'updated_at'     => now(),
            ]);

            return redirect()->back()->with('success', 'Salary updated and marked as paid.');
        } catch (\Exception $e) {
            \Log::error('Salary markPaidWithAdjustment error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to update salary.');
        }
    }
}
