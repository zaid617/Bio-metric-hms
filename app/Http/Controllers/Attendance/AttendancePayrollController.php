<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance\AttendancePayroll;
use App\Models\Employee;
use App\Models\Branch;
use App\Modules\Payroll\Services\PayrollService;
use App\Modules\Payroll\Types\PayrollAdjustmentType;
use App\Modules\Payroll\Types\PayrollAwardType;
use App\Modules\Payroll\Types\PayrollDeductionType;
use App\Modules\Payroll\Types\PayrollEarningType;
use App\Services\Attendance\AttendancePayrollService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;

class AttendancePayrollController extends Controller
{
    protected $payrollService;
    protected $modularPayrollService;

    public function __construct(AttendancePayrollService $payrollService, PayrollService $modularPayrollService)
    {
        $this->payrollService = $payrollService;
        $this->modularPayrollService = $modularPayrollService;
    }

    /**
     * Display payrolls
     */
    public function index(Request $request)
    {
        $payrolls = $this->modularPayrollService->getPayrollsForIndex($request->all(), 50);

        $branches = Branch::where('status', 'active')->get();
        $employees = Employee::query()->select('id', 'name')->orderBy('name')->get();

        return view('attendance.payroll.index', compact('payrolls', 'branches', 'employees'));
    }

    /**
     * Show form to generate payroll
     */
    public function create()
    {
        $branches = Branch::where('status', 'active')->get();
        $employees = Employee::with('branch')->select('id', 'name', 'designation', 'branch_id')->get();

        return view('attendance.payroll.generate', compact('branches', 'employees'));
    }

    /**
     * Generate and store payroll
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'employee_id' => 'nullable|exists:employees,id',
            'force_regenerate' => 'nullable|boolean',
        ]);

        try {
            $periodStart = Carbon::parse($validated['period_start']);

            $result = $this->modularPayrollService->generateMonthlyPayroll(
                (int) $periodStart->month,
                (int) $periodStart->year,
                $validated['branch_id'] ?? null,
                $validated['employee_id'] ?? null,
                (bool) ($validated['force_regenerate'] ?? false),
                Auth::user()
            );

            $createdCount = $result['created']->count();
            $skippedCount = $result['skipped']->count();

            return redirect()->route('attendance.payroll.index')
                ->with('success', "Payroll generation complete. Created: {$createdCount}, Skipped: {$skippedCount}.");
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Payroll generation failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show payroll details
     */
    public function show(AttendancePayroll $payroll)
    {
        $payroll->load(['employee', 'branch', 'approver', 'adjustments']);
        return view('attendance.payroll.show', compact('payroll'));
    }

    /**
     * Show adjustment form
     */
    public function edit(AttendancePayroll $payroll)
    {
        return view('attendance.payroll.edit', compact('payroll'));
    }

    /**
     * Update payroll adjustment
     */
    public function update(Request $request, AttendancePayroll $payroll)
    {
        $validated = $request->validate([
            'deductions' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'admin_adjustment_amount' => 'nullable|numeric',
            'admin_adjustment_note' => 'required_with:admin_adjustment_amount|nullable|string|max:500',
            'earning_code' => 'nullable|string|max:80',
            'earning_amount' => 'nullable|numeric|min:0',
            'earning_notes' => 'nullable|string|max:500',
            'deduction_code' => 'nullable|string|max:80',
            'deduction_amount' => 'nullable|numeric|min:0',
            'deduction_notes' => 'nullable|string|max:500',
            'award_code' => 'nullable|string|max:80',
            'award_amount' => 'nullable|numeric|min:0',
            'award_notes' => 'nullable|string|max:500',
        ]);

        try {
            if (isset($validated['deductions'])) {
                $this->payrollService->updateDeductions($payroll, $validated['deductions']);
            }

            if (isset($validated['bonus'])) {
                $this->payrollService->updateBonus($payroll, $validated['bonus']);
            }

            if (isset($validated['admin_adjustment_amount'])) {
                $this->payrollService->adminAdjustSettlement(
                    $payroll,
                    $validated['admin_adjustment_amount'],
                    $validated['admin_adjustment_note'],
                    Auth::user()
                );
            }

            if (!empty($validated['earning_amount'])) {
                $this->modularPayrollService->addAdjustment(
                    $payroll,
                    PayrollAdjustmentType::EARNING,
                    $validated['earning_code'] ?? PayrollEarningType::CUSTOM,
                    (float) $validated['earning_amount'],
                    $validated['earning_notes'] ?? null,
                    Auth::user()
                );
            }

            if (!empty($validated['deduction_amount'])) {
                $this->modularPayrollService->addAdjustment(
                    $payroll,
                    PayrollAdjustmentType::DEDUCTION,
                    $validated['deduction_code'] ?? PayrollDeductionType::CUSTOM,
                    (float) $validated['deduction_amount'],
                    $validated['deduction_notes'] ?? null,
                    Auth::user()
                );
            }

            if (!empty($validated['award_amount'])) {
                $this->modularPayrollService->addAdjustment(
                    $payroll,
                    PayrollAdjustmentType::AWARD,
                    $validated['award_code'] ?? PayrollAwardType::CUSTOM,
                    (float) $validated['award_amount'],
                    $validated['award_notes'] ?? null,
                    Auth::user()
                );
            }

            return redirect()->route('attendance.payroll.show', $payroll->id)
                ->with('success', 'Payroll updated successfully!');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Update failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Approve payroll
     */
    public function approve(AttendancePayroll $payroll)
    {
        try {
            $this->payrollService->approvePayroll($payroll, Auth::user());

            return redirect()->back()->with('success', 'Payroll approved successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    /**
     * Mark payroll as paid
     */
    public function markPaid(Request $request, AttendancePayroll $payroll)
    {
        $validated = $request->validate([
            'payment_method' => 'nullable|string|max:100',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        try {
            $this->payrollService->markAsPaid($payroll, $validated);

            return redirect()->back()->with('success', 'Payroll marked as paid successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to mark as paid: ' . $e->getMessage());
        }
    }

    /**
     * Regenerate payroll
     */
    public function regenerate(AttendancePayroll $payroll)
    {
        try {
            if ($payroll->status !== 'draft') {
                return redirect()->back()->with('error', 'Can only regenerate draft payrolls.');
            }

            $newPayroll = $this->payrollService->regeneratePayroll($payroll);

            return redirect()->route('attendance.payroll.show', $newPayroll->id)
                ->with('success', 'Payroll regenerated successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Regeneration failed: ' . $e->getMessage());
        }
    }

    public function employeeView(Request $request)
    {
        $validated = $request->validate([
            'period_month' => 'required|date_format:Y-m',
        ]);

        $date = Carbon::createFromFormat('Y-m', $validated['period_month']);
        $payrolls = $this->modularPayrollService->monthlyEmployeePayrolls((int) $date->month, (int) $date->year);

        return view('attendance.payroll.employee', [
            'payrolls' => $payrolls,
            'periodMonth' => $validated['period_month'],
        ]);
    }

    /**
     * Export payroll (placeholder)
     */
    public function export(Request $request)
    {
        // TODO: Implement PDF/Excel export
        return redirect()->back()->with('info', 'Export functionality coming soon!');
    }
}
