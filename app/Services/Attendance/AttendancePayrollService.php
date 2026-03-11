<?php

namespace App\Services\Attendance;

use App\Models\Attendance\AttendancePayroll;
use App\Models\Branch;
use App\Models\User;
use App\Modules\Payroll\Services\PayrollService;
use App\Modules\Payroll\Types\PayrollAdjustmentType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class AttendancePayrollService
{
    private const LEGACY_MANUAL_DEDUCTION_CODE = 'LEGACY_MANUAL_DEDUCTION';
    private const LEGACY_MANUAL_AWARD_CODE = 'LEGACY_MANUAL_AWARD';

    public function __construct(private readonly PayrollService $payrollService)
    {
    }

    /**
     * Generate payroll for a single employee
     */
    public function generatePayroll($employee, Carbon $periodStart, Carbon $periodEnd): AttendancePayroll
    {
        try {
            return $this->payrollService->calculateAndPersistPayroll($employee, $periodStart, $periodEnd);
        } catch (Exception $e) {
            Log::error("Error generating payroll for employee {$employee->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate payroll for all employees in a branch
     */
    public function generateBranchPayroll(
        Branch $branch,
        Carbon $periodStart,
        Carbon $periodEnd
    ): Collection {
        try {
            Log::info("Generating payroll for branch {$branch->id} ({$branch->name})");

            $result = $this->payrollService->generateMonthlyPayroll(
                (int) $periodStart->month,
                (int) $periodStart->year,
                $branch->id
            );

            return $result['created'];
        } catch (Exception $e) {
            Log::error("Error generating branch payroll: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Approve payroll
     */
    public function approvePayroll(AttendancePayroll $payroll, User $approver): void
    {
        try {
            $payroll->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            Log::info("Payroll {$payroll->id} approved by user {$approver->id}");
        } catch (Exception $e) {
            Log::error("Error approving payroll {$payroll->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark payroll as paid
     */
    public function markAsPaid(
        AttendancePayroll $payroll,
        array $paymentData
    ): void {
        try {
            $payroll->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $paymentData['payment_method'] ?? null,
                'payment_reference' => $paymentData['payment_reference'] ?? null,
            ]);

            Log::info("Payroll {$payroll->id} marked as paid");
        } catch (Exception $e) {
            Log::error("Error marking payroll {$payroll->id} as paid: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Admin adjustment to settlement amount
     */
    public function adminAdjustSettlement(
        AttendancePayroll $payroll,
        float $adjustmentAmount,
        string $note,
        User $admin
    ): void {
        try {
            DB::beginTransaction();

            $payroll->update([
                'admin_adjustment_amount' => $adjustmentAmount,
                'admin_adjustment_note' => $note,
                'final_salary' => (($payroll->final_salary ?? $payroll->final_settlement) + $adjustmentAmount),
                'final_settlement' => (($payroll->final_salary ?? $payroll->final_settlement) + $adjustmentAmount),
            ]);

            DB::commit();

            Log::info("Admin adjustment applied to payroll {$payroll->id} by user {$admin->id}: Amount {$adjustmentAmount}");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error applying admin adjustment to payroll {$payroll->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update deductions
     */
    public function updateDeductions(AttendancePayroll $payroll, float $deductions): void
    {
        try {
            $payroll->adjustments()
                ->where('adjustment_type', PayrollAdjustmentType::DEDUCTION)
                ->where('code', self::LEGACY_MANUAL_DEDUCTION_CODE)
                ->delete();

            if ($deductions > 0) {
                $this->payrollService->addAdjustment(
                    $payroll,
                    PayrollAdjustmentType::DEDUCTION,
                    self::LEGACY_MANUAL_DEDUCTION_CODE,
                    $deductions,
                    'Manual payroll deduction'
                );
            } else {
                $this->payrollService->regeneratePayroll($payroll);
            }

            Log::info("Updated deductions for payroll {$payroll->id}: {$deductions}");
        } catch (Exception $e) {
            Log::error("Error updating deductions for payroll {$payroll->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update bonus
     */
    public function updateBonus(AttendancePayroll $payroll, float $bonus): void
    {
        try {
            $payroll->adjustments()
                ->where('adjustment_type', PayrollAdjustmentType::AWARD)
                ->where('code', self::LEGACY_MANUAL_AWARD_CODE)
                ->delete();

            if ($bonus > 0) {
                $this->payrollService->addAdjustment(
                    $payroll,
                    PayrollAdjustmentType::AWARD,
                    self::LEGACY_MANUAL_AWARD_CODE,
                    $bonus,
                    'Manual payroll award'
                );
            } else {
                $this->payrollService->regeneratePayroll($payroll);
            }

            Log::info("Updated bonus for payroll {$payroll->id}: {$bonus}");
        } catch (Exception $e) {
            Log::error("Error updating bonus for payroll {$payroll->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate working days (excluding weekends)
     */
    public function getPayrollSummary(Carbon $periodStart, Carbon $periodEnd, $branchId = null): array
    {
        try {
            $query = AttendancePayroll::query()
                ->where('month', $periodStart->month)
                ->where('year', $periodStart->year);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $payrolls = $query->with(['employee', 'branch'])->get();

            return [
                'total_payrolls' => $payrolls->count(),
                'total_employees' => $payrolls->unique('employee_id')->count(),
                'total_calculated_salary' => $payrolls->sum('calculated_salary'),
                'total_overtime_pay' => $payrolls->sum('overtime_pay'),
                'total_deductions' => $payrolls->sum('deductions_total'),
                'total_bonus' => $payrolls->sum('awards_total'),
                'total_admin_adjustments' => $payrolls->sum('admin_adjustment_amount'),
                'total_final_settlement' => $payrolls->sum('final_salary'),
                'draft_count' => $payrolls->where('status', 'draft')->count(),
                'approved_count' => $payrolls->where('status', 'approved')->count(),
                'paid_count' => $payrolls->where('status', 'paid')->count(),
            ];
        } catch (Exception $e) {
            Log::error("Error getting payroll summary: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Regenerate payroll (recalculate)
     */
    public function regeneratePayroll(AttendancePayroll $payroll): AttendancePayroll
    {
        return $this->payrollService->regeneratePayroll($payroll);
    }
}
