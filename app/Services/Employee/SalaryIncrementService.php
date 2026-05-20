<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\SalaryIncrement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SalaryIncrementService
{
    /**
     * Record a new salary increment and immediately apply the new snapshot
     * to the employees table (effective_from is never in the future).
     */
    public function store(Employee $employee, array $data, User $createdBy): SalaryIncrement
    {
        return DB::transaction(function () use ($employee, $data, $createdBy) {
            $previousSnapshot = $employee->salarySnapshot();

            $newSnapshot = $data['increment_type'] === 'percentage'
                ? $this->applyPercentage($previousSnapshot, (float) $data['percentage'])
                : $this->applyFixed($previousSnapshot, $data);

            $previousGross = SalaryIncrement::grossFromSnapshot($previousSnapshot);
            $newGross      = SalaryIncrement::grossFromSnapshot($newSnapshot);

            $increment = SalaryIncrement::create([
                'employee_id'      => $employee->id,
                'previous_snapshot' => $previousSnapshot,
                'new_snapshot'      => $newSnapshot,
                'increment_amount'  => round($newGross - $previousGross, 2),
                'increment_type'    => $data['increment_type'],
                'effective_from'    => $data['effective_from'],
                'reason'            => $data['reason'] ?? null,
                'created_by'        => $createdBy->id,
            ]);

            $this->persistSnapshotToEmployee($employee->id, $newSnapshot);

            return $increment;
        });
    }

    /**
     * Update only the reason of an existing increment.
     * Salary values are immutable once recorded to preserve the audit trail.
     */
    public function update(SalaryIncrement $increment, array $data): SalaryIncrement
    {
        $increment->update(['reason' => $data['reason'] ?? $increment->reason]);
        return $increment->fresh();
    }

    /**
     * Soft-delete an increment. If it was the most recent one, revert the
     * employees row to the next latest increment's snapshot, or to the
     * original snapshot that was captured before any increment existed.
     */
    public function delete(SalaryIncrement $increment): void
    {
        DB::transaction(function () use ($increment) {
            $employee = Employee::findOrFail($increment->employee_id);

            // Is this the latest active (non-deleted) increment?
            $latestId = $employee->salaryIncrements()->value('id');
            $isLatest = $latestId === $increment->id;

            $increment->delete();

            if ($isLatest) {
                // After soft-delete the relationship re-queries excluding deleted rows.
                $nextLatest = $employee->salaryIncrements()->first();

                // Fall back to what the deleted increment recorded as "before".
                $snapshot = $nextLatest
                    ? $nextLatest->new_snapshot
                    : $increment->previous_snapshot;

                $this->persistSnapshotToEmployee($employee->id, $snapshot);
            }
        });
    }

    // ── private helpers ───────────────────────────────────────────────────────

    private function applyPercentage(array $snapshot, float $percentage): array
    {
        $multiplier = 1 + ($percentage / 100);
        $new        = $snapshot;

        foreach (Employee::SALARY_NUMERIC_FIELDS as $field) {
            $new[$field] = round((float) ($snapshot[$field] ?? 0) * $multiplier, 2);
        }

        // Scale each item inside the other_allowances JSON array.
        $otherAllowances = $this->normalizeOtherAllowancesArray($snapshot['other_allowances'] ?? null);
        if (!empty($otherAllowances)) {
            $new['other_allowances'] = array_map(
                fn (array $item) => [
                    'label'  => $item['label'] ?? 'Other Allowance',
                    'amount' => round((float) ($item['amount'] ?? 0) * $multiplier, 2),
                ],
                $otherAllowances
            );
        }

        return $new;
    }

    private function applyFixed(array $snapshot, array $data): array
    {
        // Start from the current snapshot so unlisted fields are preserved.
        $new = $snapshot;

        foreach (Employee::SALARY_NUMERIC_FIELDS as $field) {
            if (isset($data[$field])) {
                $new[$field] = round((float) $data[$field], 2);
            }
        }

        // other_allowances JSON entries are not editable in the fixed modal;
        // they carry over unchanged from the current snapshot.

        return $new;
    }

    /**
     * Write a snapshot's values back to the employees DB row.
     * Raw DB::table() to match the project's existing employee update pattern.
     */
    private function persistSnapshotToEmployee(int $employeeId, array $snapshot): void
    {
        $update = [];

        foreach (Employee::SALARY_NUMERIC_FIELDS as $field) {
            $update[$field] = round((float) ($snapshot[$field] ?? 0), 2);
        }

        $update['other_allowance_label'] = $snapshot['other_allowance_label'] ?? null;

        $raw = $snapshot['other_allowances'] ?? null;
        $update['other_allowances'] = $raw !== null
            ? (is_string($raw) ? $raw : json_encode($raw))
            : null;

        $update['updated_at'] = now();

        DB::table('employees')->where('id', $employeeId)->update($update);
    }

    private function normalizeOtherAllowancesArray(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? array_filter($raw, fn ($item) => is_array($item)) : [];
    }
}
