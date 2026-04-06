<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\GeneratePayrollRequest;
use App\Models\Attendance\AttendancePayroll;
use App\Models\Employee;
use App\Models\Branch;
use App\Modules\Payroll\Services\PayrollService;
use App\Modules\Payroll\Types\PayrollAdjustmentType;
use App\Modules\Payroll\Types\PayrollAwardType;
use App\Modules\Payroll\Types\PayrollDeductionType;
use App\Modules\Payroll\Types\PayrollEarningType;
use App\Services\Attendance\AttendancePayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use ZipArchive;
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
     * Display payrolls with summary stats
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 10;
        $payrolls = $this->modularPayrollService->getPayrollsForIndex($request->all(), $perPage);

        $branches  = Branch::where('status', 'active')->get();
        $employees = Employee::query()->select('id', 'name')->orderBy('name')->get();

        // Dashboard stats for the selected/current month
        $statMonth = $request->input('period_month') ? Carbon::parse($request->input('period_month')) : now();
        $stats = $this->modularPayrollService->getDashboardStats((int) $statMonth->month, (int) $statMonth->year);

        return view('attendance.payroll.index', compact('payrolls', 'branches', 'employees', 'stats', 'statMonth'));
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
    public function store(GeneratePayrollRequest $request)
    {
        $validated = $request->validated();

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
            $updatedCount = collect($result['updated'] ?? [])->count();
            $skippedCount = $result['skipped']->count();

            return redirect()->route('attendance.payroll.index')
                ->with('success', "Payroll generation complete. Created: {$createdCount}, Updated: {$updatedCount}, Skipped: {$skippedCount}.");
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
        $payroll->load(['employee', 'branch', 'approver', 'adjustments.creator']);
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
            'earnings' => 'nullable|array',
            'earnings.*.code' => 'nullable|string|max:80',
            'earnings.*.amount' => 'nullable|numeric|min:0.01',
            'earnings.*.notes' => 'nullable|string|max:500',
            'deductions_items' => 'nullable|array',
            'deductions_items.*.code' => 'nullable|string|max:80',
            'deductions_items.*.amount' => 'nullable|numeric|min:0.01',
            'deductions_items.*.notes' => 'nullable|string|max:500',
            'awards' => 'nullable|array',
            'awards.*.code' => 'nullable|string|max:80',
            'awards.*.amount' => 'nullable|numeric|min:0.01',
            'awards.*.notes' => 'nullable|string|max:500',
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

            $batchAdjustments = [];
            $containsManualOvertime = false;

            $appendRows = static function (
                array $rows,
                string $type,
                string $defaultCode,
                array &$batchAdjustments,
                bool &$containsManualOvertime
            ): void {
                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $amount = (float) ($row['amount'] ?? 0);
                    if ($amount <= 0) {
                        continue;
                    }

                    $code = strtoupper(trim((string) ($row['code'] ?? '')));
                    if ($type === PayrollAdjustmentType::EARNING && $code === PayrollEarningType::OVERTIME) {
                        $containsManualOvertime = true;
                        continue;
                    }

                    $notes = isset($row['notes']) && $row['notes'] !== '' ? (string) $row['notes'] : null;

                    $batchAdjustments[] = [
                        'adjustment_type' => $type,
                        'code' => $code !== '' ? $code : $defaultCode,
                        'amount' => $amount,
                        'notes' => $notes,
                    ];
                }
            };

            $appendRows(
                $validated['earnings'] ?? [],
                PayrollAdjustmentType::EARNING,
                PayrollEarningType::CUSTOM,
                $batchAdjustments,
                $containsManualOvertime
            );

            $appendRows(
                $validated['awards'] ?? [],
                PayrollAdjustmentType::AWARD,
                PayrollAwardType::CUSTOM,
                $batchAdjustments,
                $containsManualOvertime
            );

            $appendRows(
                $validated['deductions_items'] ?? [],
                PayrollAdjustmentType::DEDUCTION,
                PayrollDeductionType::CUSTOM,
                $batchAdjustments,
                $containsManualOvertime
            );

            // Backward compatibility for older single-row payloads.
            if (!empty($validated['earning_amount'])) {
                $legacyCode = strtoupper((string) ($validated['earning_code'] ?? ''));
                if ($legacyCode === PayrollEarningType::OVERTIME) {
                    $containsManualOvertime = true;
                } else {
                    $batchAdjustments[] = [
                        'adjustment_type' => PayrollAdjustmentType::EARNING,
                        'code' => $legacyCode !== '' ? $legacyCode : PayrollEarningType::CUSTOM,
                        'amount' => (float) $validated['earning_amount'],
                        'notes' => $validated['earning_notes'] ?? null,
                    ];
                }
            }

            if (!empty($validated['award_amount'])) {
                $batchAdjustments[] = [
                    'adjustment_type' => PayrollAdjustmentType::AWARD,
                    'code' => !empty($validated['award_code']) ? $validated['award_code'] : PayrollAwardType::CUSTOM,
                    'amount' => (float) $validated['award_amount'],
                    'notes' => $validated['award_notes'] ?? null,
                ];
            }

            if (!empty($validated['deduction_amount'])) {
                $batchAdjustments[] = [
                    'adjustment_type' => PayrollAdjustmentType::DEDUCTION,
                    'code' => !empty($validated['deduction_code']) ? $validated['deduction_code'] : PayrollDeductionType::CUSTOM,
                    'amount' => (float) $validated['deduction_amount'],
                    'notes' => $validated['deduction_notes'] ?? null,
                ];
            }

            if ($containsManualOvertime) {
                return redirect()->back()
                    ->with('error', 'Overtime amount is calculated automatically from attendance. Please remove manual overtime adjustment entries.')
                    ->withInput();
            }

            if (!empty($batchAdjustments)) {
                $payroll = $this->modularPayrollService->addAdjustments($payroll, $batchAdjustments, Auth::user());
            }

            if (isset($validated['admin_adjustment_amount'])) {
                $this->payrollService->adminAdjustSettlement(
                    $payroll,
                    (float) $validated['admin_adjustment_amount'],
                    $validated['admin_adjustment_note'] ?? null,
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

    public function previewPayslip(AttendancePayroll $payroll)
    {
        $payroll->load(['employee', 'branch']);

        return view('payroll.payslip-pdf', $this->buildPayslipViewData($payroll));
    }

    public function downloadPayslip(AttendancePayroll $payroll)
    {
        $payroll->load(['employee', 'branch']);

        $pdf = Pdf::loadView('payroll.payslip-pdf', $this->buildPayslipViewData($payroll))
            ->setPaper('a4', 'portrait');

        return $pdf->download($this->buildPayslipFilename($payroll));
    }

    public function bulkDownloadPayslips(Request $request)
    {
        $validated = $request->validate([
            'payroll_ids' => 'required|array|min:1',
            'payroll_ids.*' => 'required|integer|exists:attendance_payrolls,id',
        ]);

        $payrolls = AttendancePayroll::query()
            ->with(['employee', 'branch'])
            ->whereIn('id', $validated['payroll_ids'])
            ->orderBy('id')
            ->get();

        if ($payrolls->isEmpty()) {
            return redirect()->back()->with('error', 'No payroll records found for bulk payslip download.');
        }

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . DIRECTORY_SEPARATOR . 'payslips_' . now()->format('Ymd_His') . '_' . uniqid() . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Unable to create payslip ZIP archive.');
        }

        foreach ($payrolls as $payroll) {
            $pdfContent = Pdf::loadView('payroll.payslip-pdf', $this->buildPayslipViewData($payroll))
                ->setPaper('a4', 'portrait')
                ->output();

            $zip->addFromString($this->buildPayslipFilename($payroll), $pdfContent);
        }

        $zip->close();

        $zipDownloadName = 'Payslips_' . now()->format('M_Y') . '.zip';
        return response()->download($zipPath, $zipDownloadName)->deleteFileAfterSend(true);
    }

    private function buildPayslipViewData(AttendancePayroll $payroll): array
    {
        $netSalary = (float) ($payroll->final_salary ?? $payroll->final_settlement ?? 0);
        $periodDate = Carbon::create((int) $payroll->year, (int) $payroll->month, 1);
        $attendanceData = (array) data_get($payroll->payslip_data, 'attendance', []);
        $earningsBreakdown = collect($payroll->earnings_breakdown ?: data_get($payroll->payslip_data, 'earnings', []))
            ->filter(fn ($line) => is_array($line))
            ->values()
            ->all();
        $awardsBreakdown = collect($payroll->awards_breakdown ?: data_get($payroll->payslip_data, 'awards', []))
            ->filter(fn ($line) => is_array($line))
            ->values()
            ->all();
        $deductionsBreakdown = collect($payroll->deductions_breakdown ?: data_get($payroll->payslip_data, 'deductions', []))
            ->filter(fn ($line) => is_array($line))
            ->values()
            ->all();
        $warnings = collect((array) data_get($payroll->payslip_data, 'warnings', []))
            ->filter(fn ($warning) => is_string($warning) && trim($warning) !== '')
            ->values();

        return [
            'payroll' => $payroll,
            'periodDate' => $periodDate,
            'periodLabel' => $periodDate->format('F Y'),
            'attendanceData' => [
                'working_days' => (int) ($attendanceData['working_days'] ?? $payroll->total_working_days ?? 0),
                'present_days' => (int) ($attendanceData['present_days'] ?? $payroll->present_days ?? 0),
                'absent_days' => (int) ($attendanceData['absent_days'] ?? $payroll->absent_days ?? 0),
                'leave_days' => (int) ($attendanceData['leave_days'] ?? $payroll->leave_days ?? 0),
                'late_count' => (int) ($attendanceData['late_count'] ?? $payroll->total_late_count ?? $payroll->late_days ?? 0),
                'late_minutes' => (int) ($attendanceData['late_minutes'] ?? $payroll->total_late_minutes ?? 0),
                'overtime_hours' => round((float) ($attendanceData['overtime_hours'] ?? $payroll->total_overtime_hours ?? $payroll->overtime_hours ?? 0), 2),
            ],
            'warnings' => $warnings,
            'earningsBreakdown' => $earningsBreakdown,
            'awardsBreakdown' => $awardsBreakdown,
            'deductionsBreakdown' => $deductionsBreakdown,
            'amountInWords' => $this->amountInWords($netSalary),
            'generatedAt' => now(),
        ];
    }

    private function buildPayslipFilename(AttendancePayroll $payroll): string
    {
        $employeeName = Str::slug((string) ($payroll->employee?->name ?? 'Employee'), '_');
        $month = str_pad((string) $payroll->month, 2, '0', STR_PAD_LEFT);
        $year = (string) $payroll->year;

        return 'Payslip_' . $employeeName . '_' . $month . '_' . $year . '.pdf';
    }

    private function amountInWords(float $amount): string
    {
        $amount = round($amount, 2);

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
            $whole = (int) floor($amount);
            $fraction = (int) round(($amount - $whole) * 100);
            $wholeWords = ucfirst((string) $formatter->format($whole));

            if ($fraction > 0) {
                $fractionWords = (string) $formatter->format($fraction);
                return $wholeWords . ' rupees and ' . $fractionWords . ' paisa only';
            }

            return $wholeWords . ' rupees only';
        }

        return number_format($amount, 2) . ' rupees only';
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
