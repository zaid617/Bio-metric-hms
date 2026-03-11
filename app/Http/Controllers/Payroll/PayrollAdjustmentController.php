<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollAdjustmentRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Modules\Payroll\Models\PayrollAdjustment;
use App\Modules\Payroll\Repositories\PayrollRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollAdjustmentController extends Controller
{
    public function __construct(private readonly PayrollRepository $repository)
    {
    }

    /**
     * List all pre-payroll (standalone) adjustments with filters.
     */
    public function index(Request $request)
    {
        $query = PayrollAdjustment::query()
            ->with(['employee', 'creator'])
            ->whereNull('payroll_id');   // standalone only

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('type')) {
            $query->where('adjustment_type', $request->input('type'));
        }

        if ($request->filled('month')) {
            $query->where('month', $request->integer('month'));
        }

        if ($request->filled('year')) {
            $query->where('year', $request->integer('year'));
        }

        $adjustments = $query->latest()->paginate(20)->withQueryString();
        $employees   = Employee::orderBy('name')->select('id', 'name', 'designation')->get();

        return view('attendance.payroll.adjustments.index', compact('adjustments', 'employees'));
    }

    /**
     * Show the create form.
     */
    public function create(Request $request)
    {
        $employees    = Employee::with('branch')->orderBy('name')->get();
        $codes        = StorePayrollAdjustmentRequest::availableCodes();
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        // Pre-fill from query params (e.g. coming from payroll detail page)
        $prefill = [
            'employee_id'     => $request->integer('employee_id') ?: null,
            'month'           => $request->integer('month') ?: $currentMonth,
            'year'            => $request->integer('year')  ?: $currentYear,
            'adjustment_type' => $request->input('adjustment_type'),
        ];

        return view('attendance.payroll.adjustments.create', compact('employees', 'codes', 'prefill'));
    }

    /**
     * Store a new standalone adjustment.
     */
    public function store(StorePayrollAdjustmentRequest $request)
    {
        $data = $request->validated();

        $this->repository->addAdjustment([
            'payroll_id'      => null,   // standalone — not linked to any payroll yet
            'employee_id'     => $data['employee_id'],
            'month'           => (int) $data['month'],
            'year'            => (int) $data['year'],
            'adjustment_type' => $data['adjustment_type'],
            'code'            => $data['code'],
            'title'           => $data['title'] ?? null,
            'amount'          => (float) $data['amount'],
            'notes'           => $data['notes'] ?? $data['reason'] ?? null,
            'reason'          => $data['reason'] ?? null,
            'meta'            => null,
            'created_by'      => Auth::id(),
        ]);

        return redirect()->route('attendance.payroll.adjustments.index')
            ->with('success', 'Payroll adjustment saved. It will be applied automatically when payroll is generated.');
    }

    /**
     * Show a single adjustment.
     */
    public function show(PayrollAdjustment $adjustment)
    {
        $adjustment->load(['employee', 'payroll', 'creator']);
        return view('attendance.payroll.adjustments.show', compact('adjustment'));
    }

    /**
     * Show edit form.
     */
    public function edit(PayrollAdjustment $adjustment)
    {
        // Only allow editing standalone (unlinked) adjustments
        if (!$adjustment->isStandalone()) {
            return redirect()->back()
                ->with('error', 'This adjustment is linked to a generated payroll and cannot be edited. Use the payroll edit page instead.');
        }

        $employees = Employee::with('branch')->orderBy('name')->get();
        $codes     = StorePayrollAdjustmentRequest::availableCodes();

        return view('attendance.payroll.adjustments.edit', compact('adjustment', 'employees', 'codes'));
    }

    /**
     * Update a standalone adjustment.
     */
    public function update(StorePayrollAdjustmentRequest $request, PayrollAdjustment $adjustment)
    {
        if (!$adjustment->isStandalone()) {
            return redirect()->back()
                ->with('error', 'Cannot edit an adjustment that is already linked to a generated payroll.');
        }

        $data = $request->validated();

        $adjustment->update([
            'employee_id'     => $data['employee_id'],
            'month'           => (int) $data['month'],
            'year'            => (int) $data['year'],
            'adjustment_type' => $data['adjustment_type'],
            'code'            => $data['code'],
            'title'           => $data['title'] ?? null,
            'amount'          => (float) $data['amount'],
            'notes'           => $data['notes'] ?? $data['reason'] ?? null,
            'reason'          => $data['reason'] ?? null,
        ]);

        return redirect()->route('attendance.payroll.adjustments.index')
            ->with('success', 'Adjustment updated successfully.');
    }

    /**
     * Delete a standalone adjustment.
     */
    public function destroy(PayrollAdjustment $adjustment)
    {
        if (!$adjustment->isStandalone()) {
            return redirect()->back()
                ->with('error', 'Cannot delete an adjustment that is linked to a generated payroll.');
        }

        $adjustment->delete();

        return redirect()->route('attendance.payroll.adjustments.index')
            ->with('success', 'Adjustment deleted.');
    }
}
