<?php

namespace App\Services\Attendance;

use App\Models\Attendance\AttendanceDevice;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Attendance\AttendanceSyncLog;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AttendanceSyncService
{
    protected $zkService;

    public function __construct(ZKTecoService $zkService)
    {
        $this->zkService = $zkService;
    }

    /**
     * Sync device users from ZKTeco device directly into employees table
     */
    public function syncDeviceUsers(AttendanceDevice $device, ?Collection $users = null): array
    {
        $startedAt = now();
        $recordsFetched = 0;
        $recordsNew = 0;
        $recordsUpdated = 0;
        $recordsSkipped = 0;
        $errorMessage = null;
        $status = 'success';

        try {
            Log::info("Starting user sync for device: {$device->device_name}");

            // Fetch users from device
            $users = ($users ?? $this->zkService->getUsers($device))
                ->filter(function ($userData) {
                    return !empty($this->normalizeDeviceUserId($userData['user_id_on_device'] ?? null));
                });

            $linkedEmployees = Employee::where('device_id', $device->id)
                ->whereNotNull('user_id_on_device')
                ->orderBy('id')
                ->get();

            $employeesByDeviceUserId = collect([]);
            foreach ($linkedEmployees as $linkedEmployee) {
                $normalizedLinkedId = $this->normalizeDeviceUserId($linkedEmployee->user_id_on_device);
                if ($normalizedLinkedId !== '' && !$employeesByDeviceUserId->has($normalizedLinkedId)) {
                    $employeesByDeviceUserId->put($normalizedLinkedId, $linkedEmployee);
                }
            }

            $recordsFetched = $users->count();

            if ($recordsFetched === 0) {
                $status = 'failed';
                $errorMessage = 'No users fetched from device';
            } else {
                foreach ($users as $userData) {
                    $deviceUserId = $this->normalizeDeviceUserId($userData['user_id_on_device'] ?? null);

                    if ($deviceUserId === '') {
                        $recordsSkipped++;
                        continue;
                    }

                    // Check if an employee is already linked to this device user
                    $employee = $employeesByDeviceUserId->get($deviceUserId);

                    if ($employee) {
                        // Update name if it changed on the device
                        if (!empty($userData['name']) && $employee->name !== $userData['name']) {
                            $employee->update(['name' => $userData['name']]);
                        }
                        $recordsUpdated++;
                    } else {
                        $matched = $this->findSafeEmployeeMatchForDeviceUser(
                            $device,
                            $deviceUserId,
                            $userData['name'] ?? null
                        );

                        if ($matched) {
                            if (!$this->canLinkEmployeeToDeviceUser($matched, $device, $deviceUserId)) {
                                $recordsSkipped++;
                                Log::warning("Skipped conflicting mapping for employee {$matched->id} and device user {$deviceUserId} on device {$device->device_name}");
                                continue;
                            }

                            // Link existing employee to this device
                            $matched->update([
                                'device_id' => $device->id,
                                'user_id_on_device' => $deviceUserId,
                            ]);
                            $employeesByDeviceUserId->put($deviceUserId, $matched);

                            if (!empty($userData['name']) && $matched->name !== $userData['name']) {
                                $matched->update(['name' => $userData['name']]);
                            }

                            $recordsUpdated++;
                            Log::info("Linked existing employee {$matched->name} to device user {$deviceUserId} on device {$device->device_name}");
                        } else {
                            // Create a new employee from this device user
                            $createdEmployee = Employee::create([
                                'name'              => !empty($userData['name']) ? $userData['name'] : "Device User {$deviceUserId}",
                                'designation'       => 'Employee',
                                'branch_id'         => $device->branch_id,
                                'basic_salary'      => 0,
                                'working_hours'     => (float) config('payroll.default_shift_hours', 8),
                                'shift'             => '',
                                'shift_start_time'  => config('payroll.shift_start', '09:00'),
                                'device_id'         => $device->id,
                                'user_id_on_device' => $deviceUserId,
                            ]);
                            $employeesByDeviceUserId->put($deviceUserId, $createdEmployee);
                            $recordsNew++;
                            Log::info("Created new employee from device user: {$userData['name']} (device: {$device->device_name}, branch_id: {$device->branch_id})");
                        }
                    }
                }

                Log::info("User sync completed for device {$device->device_name}: {$recordsNew} new, {$recordsUpdated} updated");
            }
        } catch (Exception $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
            Log::error("User sync failed for device {$device->device_name}: " . $e->getMessage());
        }

        // Log sync
        AttendanceSyncLog::create([
            'device_id' => $device->id,
            'sync_type' => 'users',
            'status' => $status,
            'records_fetched' => $recordsFetched,
            'records_new' => $recordsNew,
            'records_duplicate' => $recordsUpdated,
            'error_message' => $errorMessage,
            'started_at' => $startedAt,
            'completed_at' => now(),
            'created_at' => now(),
        ]);

        return [
            'success' => $status === 'success',
            'records_fetched' => $recordsFetched,
            'records_new' => $recordsNew,
            'records_updated' => $recordsUpdated,
            'records_skipped' => $recordsSkipped,
            'message' => $errorMessage ?? "Successfully synced {$recordsFetched} users",
        ];
    }

    /**
     * Sync attendance logs from device
     */
    public function syncAttendanceLogs(AttendanceDevice $device, bool $forceFull = false, ?Collection $fetchedLogs = null): array
    {
        $startedAt = now();
        $recordsFetched = 0;
        $recordsNew = 0;
        $recordsDuplicate = 0;
        $recordsSkipped = 0;
        $errorMessage = null;
        $status = 'success';

        try {
            Log::info("Starting attendance sync for device: {$device->device_name}");

            // On first sync fetch all history. Manual sync can also force a full pull.
            $isFirstSync = is_null($device->last_synced_at);
            $from = ($isFirstSync || $forceFull) ? null : Carbon::parse($device->last_synced_at);

            if ($isFirstSync) {
                Log::info("First sync for device {$device->device_name} — fetching all historical logs");
            }

            if ($forceFull && !$isFirstSync) {
                Log::info("Force full sync requested for device {$device->device_name} — fetching complete attendance history");
            }

            $fetchedLogs = $fetchedLogs ?? $this->zkService->getAttendanceLogs($device, $from);
            $recordsFetched = $fetchedLogs->count();

            $logs = $fetchedLogs->map(function ($logData) {
                $normalizedDeviceUserId = $this->normalizeDeviceUserId($logData['user_id_on_device'] ?? null);

                if ($normalizedDeviceUserId === '') {
                    return null;
                }

                return [
                    'user_id_on_device' => $normalizedDeviceUserId,
                    'punch_time' => $logData['punch_time'] instanceof Carbon
                        ? $logData['punch_time']
                        : Carbon::parse($logData['punch_time']),
                ];
            })->filter(function ($logData) {
                return $logData !== null;
            });

            $recordsSkipped = max(0, $recordsFetched - $logs->count());

            if ($recordsFetched === 0) {
                Log::info("No new attendance logs for device {$device->device_name}");
            } elseif ($logs->isEmpty()) {
                Log::warning("Fetched {$recordsFetched} logs for device {$device->device_name}, but none had valid user IDs. Sync cursor not advanced.");
            } else {
                $linkedEmployees = Employee::where('device_id', $device->id)
                    ->whereNotNull('user_id_on_device')
                    ->orderBy('id')
                    ->get();

                $employeesByDeviceUserId = collect([]);
                $employeesByNumericUserId = collect([]);

                foreach ($linkedEmployees as $employee) {
                    $normalizedId = $this->normalizeDeviceUserId($employee->user_id_on_device);

                    if ($normalizedId === '') {
                        continue;
                    }

                    if (!$employeesByDeviceUserId->has($normalizedId)) {
                        $employeesByDeviceUserId->put($normalizedId, $employee);
                    }

                    if (is_numeric($normalizedId)) {
                        $numericVariant = (string) ((int) $normalizedId);
                        if ($numericVariant !== '' && !$employeesByNumericUserId->has($numericVariant)) {
                            $employeesByNumericUserId->put($numericVariant, $employee);
                        }
                    }
                }

                $matchedPunches = collect([]);

                foreach ($logs as $logData) {
                    $employee = $this->findEmployeeByDeviceUserId(
                        $device,
                        $logData['user_id_on_device'],
                        $employeesByDeviceUserId,
                        $employeesByNumericUserId
                    );

                    if (!$employee) {
                        $recordsSkipped++;
                        continue;
                    }

                    $matchedPunches->push([
                        'employee_id' => (int) $employee->id,
                        'employee' => $employee,
                        'attendance_date' => $logData['punch_time']->toDateString(),
                        'punch_time' => $logData['punch_time'],
                    ]);
                }

                if ($matchedPunches->isEmpty()) {
                    Log::warning("Fetched {$recordsFetched} logs for device {$device->device_name}, but none matched linked employees. Sync cursor not advanced.");
                } else {
                    $groupedPunches = $matchedPunches->groupBy(function ($punchData) {
                        return $punchData['employee_id'] . '_' . $punchData['attendance_date'];
                    });

                    $affectedEmployeeIds = $groupedPunches->map(function ($group) {
                        return (int) $group->first()['employee_id'];
                    })->unique()->values();

                    $affectedDates = $groupedPunches->map(function ($group) {
                        return $group->first()['attendance_date'];
                    })->unique()->values();

                    $existingRecordsByKey = collect([]);
                    if ($affectedEmployeeIds->isNotEmpty() && $affectedDates->isNotEmpty()) {
                        $existingRecordsByKey = AttendanceRecord::whereIn('employee_id', $affectedEmployeeIds)
                            ->whereIn('attendance_date', $affectedDates)
                            ->get()
                            ->keyBy(function (AttendanceRecord $record) {
                                $dateKey = $record->attendance_date instanceof Carbon
                                    ? $record->attendance_date->toDateString()
                                    : (string) $record->attendance_date;

                                return $record->employee_id . '_' . $dateKey;
                            });
                    }

                    $shiftRuleCache = $this->preloadShiftRules($linkedEmployees);

                    DB::transaction(function () use (
                        $device,
                        $groupedPunches,
                        &$existingRecordsByKey,
                        &$shiftRuleCache,
                        &$recordsNew,
                        &$recordsDuplicate,
                        &$recordsSkipped
                    ) {
                        foreach ($groupedPunches as $groupKey => $punchGroup) {
                            $sortedPunches = $punchGroup->sortBy(function ($item) {
                                return $item['punch_time']->getTimestamp();
                            })->values();

                            $firstPunch = $sortedPunches->first();
                            $lastPunch = $sortedPunches->last();

                            if (!$firstPunch || !$lastPunch) {
                                $recordsSkipped++;
                                continue;
                            }

                            /** @var Employee $employee */
                            $employee = $firstPunch['employee'];
                            $attendanceDate = $firstPunch['attendance_date'];
                            $checkIn = $firstPunch['punch_time']->format('H:i:s');
                            $checkOut = $lastPunch['punch_time']->format('H:i:s');

                            if ($checkOut === $checkIn) {
                                $checkOut = null;
                            }

                            $attendanceRecord = $existingRecordsByKey->get($groupKey);
                            $isNewRecord = !$attendanceRecord;

                            if ($attendanceRecord && $attendanceRecord->is_manually_adjusted) {
                                $recordsSkipped++;
                                continue;
                            }

                            if (!$attendanceRecord) {
                                $attendanceRecord = new AttendanceRecord([
                                    'employee_id' => $employee->id,
                                    'attendance_date' => $attendanceDate,
                                ]);
                            }

                            $recordChanged = false;

                            if ($isNewRecord) {
                                $attendanceRecord->branch_id = $employee->branch_id;
                                $attendanceRecord->device_id = $device->id;
                                $attendanceRecord->status = 'present';
                                $recordChanged = true;
                            }

                            if ((string) ($attendanceRecord->check_in ?? '') !== (string) $checkIn) {
                                $attendanceRecord->check_in = $checkIn;
                                $recordChanged = true;
                            }

                            if ((string) ($attendanceRecord->check_out ?? '') !== (string) ($checkOut ?? '')) {
                                $attendanceRecord->check_out = $checkOut;
                                $recordChanged = true;
                            }

                            if (!$recordChanged && !$attendanceRecord->isDirty()) {
                                $recordsDuplicate++;
                                continue;
                            }

                            $attendanceRecord->setRelation('employee', $employee);
                            $shiftRule = $this->getCachedShiftRuleForDate((int) $employee->id, $attendanceDate, $shiftRuleCache);
                            $this->applyWorkingTimeCalculation($attendanceRecord, $shiftRule);
                            $attendanceRecord->save();

                            if ($isNewRecord) {
                                $recordsNew++;
                            }

                            $existingRecordsByKey->put($groupKey, $attendanceRecord);
                        }
                    });

                    $latestPunchTime = $matchedPunches->max('punch_time');
                    if ($latestPunchTime instanceof Carbon) {
                        $device->update(['last_synced_at' => $latestPunchTime]);
                    } else {
                        $device->update(['last_synced_at' => now()]);
                    }

                    Log::info("Attendance sync completed for device {$device->device_name}: {$recordsNew} new, {$recordsDuplicate} duplicates");
                }
            }
        } catch (Exception $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
            Log::error("Attendance sync failed for device {$device->device_name}: " . $e->getMessage());
        }

        // Log sync
        AttendanceSyncLog::create([
            'device_id' => $device->id,
            'sync_type' => 'attendance',
            'status' => $status,
            'records_fetched' => $recordsFetched,
            'records_new' => $recordsNew,
            'records_duplicate' => $recordsDuplicate,
            'error_message' => $errorMessage,
            'started_at' => $startedAt,
            'completed_at' => now(),
            'created_at' => now(),
        ]);

        return [
            'success' => $status === 'success',
            'records_fetched' => $recordsFetched,
            'records_new' => $recordsNew,
            'records_duplicate' => $recordsDuplicate,
            'records_skipped' => $recordsSkipped,
            'message' => $errorMessage ?? "Successfully synced {$recordsNew} new attendance logs",
        ];
    }

    /**
     * Normalize device user ID for matching operations.
     */
    protected function normalizeDeviceUserId($value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(str_replace("\0", '', (string) $value));
    }

    /**
     * Find employee mapped to device user ID (exact first, numeric fallback second).
     */
    protected function findEmployeeByDeviceUserId(
        AttendanceDevice $device,
        $deviceUserId,
        ?Collection $employeesByDeviceUserId = null,
        ?Collection $employeesByNumericUserId = null
    ): ?Employee
    {
        $normalizedId = $this->normalizeDeviceUserId($deviceUserId);

        if ($normalizedId === '') {
            return null;
        }

        if ($employeesByDeviceUserId !== null) {
            $employee = $employeesByDeviceUserId->get($normalizedId);
            if ($employee) {
                return $employee;
            }

            if (is_numeric($normalizedId) && $employeesByNumericUserId !== null) {
                $numericVariant = (string) ((int) $normalizedId);
                if ($numericVariant !== '') {
                    return $employeesByNumericUserId->get($numericVariant);
                }
            }

            return null;
        }

        $employee = Employee::where('device_id', $device->id)
            ->where('user_id_on_device', $normalizedId)
            ->first();

        if ($employee) {
            return $employee;
        }

        if (is_numeric($normalizedId)) {
            $numericVariant = (string) ((int) $normalizedId);

            if ($numericVariant !== $normalizedId) {
                return Employee::where('device_id', $device->id)
                    ->where('user_id_on_device', $numericVariant)
                    ->first();
            }
        }

        return null;
    }

    /**
     * Preload shift rules for employees in one query and cache by employee ID.
     */
    protected function preloadShiftRules(Collection $employees): array
    {
        $shiftRuleCache = [];
        $defaultShiftStart = (string) config('payroll.shift_start', '09:00');
        $defaultGrace = (int) config('payroll.late_grace_minutes', 15);
        $defaultWorkingMinutes = (int) round(max(0, (float) config('payroll.default_shift_hours', 8)) * 60);

        $hasShiftTable = DB::getSchemaBuilder()->hasTable('attendance_shifts');
        $shiftRowsByName = collect([]);

        if ($hasShiftTable) {
            $shiftNames = $employees
                ->pluck('shift')
                ->map(function ($shiftName) {
                    return strtolower(trim((string) $shiftName));
                })
                ->filter()
                ->unique()
                ->values();

            if ($shiftNames->isNotEmpty()) {
                $placeholders = implode(',', array_fill(0, $shiftNames->count(), '?'));

                $shiftRows = DB::table('attendance_shifts')
                    ->whereRaw('LOWER(shift_name) IN (' . $placeholders . ')', $shiftNames->all())
                    ->get();

                $shiftRowsByName = $shiftRows->groupBy(function ($row) {
                    return strtolower(trim((string) ($row->shift_name ?? '')));
                });
            }
        }

        foreach ($employees as $employee) {
            $employeeId = (int) $employee->id;
            $shiftStart = !empty($employee?->shift_start_time)
                ? substr((string) $employee->shift_start_time, 0, 5)
                : $defaultShiftStart;
            $graceMinutes = $defaultGrace;
            $shiftName = strtolower(trim((string) ($employee?->shift ?? '')));

            if ($hasShiftTable && $shiftName !== '' && $shiftRowsByName->has($shiftName)) {
                $candidates = collect($shiftRowsByName->get($shiftName));
                $matchedShift = null;

                if (!empty($employee?->branch_id)) {
                    $matchedShift = $candidates->first(function ($row) use ($employee) {
                        return (int) ($row->branch_id ?? 0) === (int) $employee->branch_id;
                    });
                }

                if (!$matchedShift) {
                    $matchedShift = $candidates->first(function ($row) {
                        return $row->branch_id === null;
                    });
                }

                if (!$matchedShift) {
                    $matchedShift = $candidates->first();
                }

                if ($matchedShift) {
                    $candidateStart = substr((string) ($matchedShift->start_time ?? ''), 0, 5);
                    if (preg_match('/^\d{2}:\d{2}$/', $candidateStart)) {
                        $shiftStart = $candidateStart;
                    }

                    $graceMinutes = (int) ($matchedShift->grace_period_minutes ?? $graceMinutes);
                }
            }

            if (!preg_match('/^\d{2}:\d{2}$/', $shiftStart)) {
                $shiftStart = $defaultShiftStart;
            }

            $workingHours = (float) ($employee?->working_hours ?? config('payroll.default_shift_hours', 8));

            $shiftRuleCache[$employeeId] = [
                'shift_start_time' => $shiftStart,
                'grace_minutes' => max(0, $graceMinutes),
                'working_minutes' => max(0, (int) round($workingHours * 60)),
            ];
        }

        return $shiftRuleCache;
    }

    /**
     * Build a date-specific shift rule from a preloaded employee cache.
     */
    protected function getCachedShiftRuleForDate(int $employeeId, string $attendanceDate, array &$shiftRuleCache): array
    {
        $defaultShiftStart = (string) config('payroll.shift_start', '09:00');
        $defaultGrace = (int) config('payroll.late_grace_minutes', 15);
        $defaultWorkingMinutes = (int) round(max(0, (float) config('payroll.default_shift_hours', 8)) * 60);

        $cachedRule = $shiftRuleCache[$employeeId] ?? [
            'shift_start_time' => $defaultShiftStart,
            'grace_minutes' => max(0, $defaultGrace),
            'working_minutes' => $defaultWorkingMinutes,
        ];

        if (!preg_match('/^\d{2}:\d{2}$/', (string) $cachedRule['shift_start_time'])) {
            $cachedRule['shift_start_time'] = $defaultShiftStart;
        }

        $shiftStartAt = Carbon::parse($attendanceDate . ' ' . $cachedRule['shift_start_time']);

        return [
            'shift_start_at' => $shiftStartAt,
            'shift_end_at' => (clone $shiftStartAt)->addMinutes((int) $cachedRule['working_minutes']),
            'grace_minutes' => (int) $cachedRule['grace_minutes'],
        ];
    }

    /**
     * Apply working time and status attributes without saving.
     */
    protected function applyWorkingTimeCalculation(AttendanceRecord $record, array $shiftRule): void
    {
        if (!$record->check_in) {
            return;
        }

        // Ensure attendance_date is just the date part (no time)
        $dateOnly = $record->attendance_date instanceof \Carbon\Carbon
            ? $record->attendance_date->toDateString()
            : date('Y-m-d', strtotime($record->attendance_date));

        $checkIn = Carbon::parse($dateOnly . ' ' . $record->check_in);
        $shiftStartAt = $shiftRule['shift_start_at'];
        $shiftEndAt = $shiftRule['shift_end_at'];
        $graceMinutes = $shiftRule['grace_minutes'];

        if ($record->check_out) {
            $checkOut = Carbon::parse($dateOnly . ' ' . $record->check_out);

            // Handle night shift (checkout next day)
            if ($checkOut->lt($checkIn)) {
                $checkOut->addDay();
            }

            $record->total_working_minutes = $checkIn->diffInMinutes($checkOut);
            $record->overtime_minutes = $checkOut->gt($shiftEndAt)
                ? $shiftEndAt->diffInMinutes($checkOut)
                : 0;
            $record->is_checkout_missing = false;
        } else {
            $record->overtime_minutes = 0;
        }

        $deadline = (clone $shiftStartAt)->addMinutes($graceMinutes);
        $record->is_late = $checkIn->gt($deadline);
        $record->late_minutes = $checkIn->gt($shiftStartAt)
            ? $shiftStartAt->diffInMinutes($checkIn)
            : 0;
        $record->status = $record->is_late ? 'late' : 'present';

        // Check for missing checkout
        if (!$record->check_out) {
            $record->is_checkout_missing = true;
        }
    }

    /**
     * Find a safe match for a device user from existing employees.
     */
    protected function findSafeEmployeeMatchForDeviceUser(AttendanceDevice $device, string $deviceUserId, ?string $deviceUserName = null): ?Employee
    {
        $strategies = (array) config('zkteco.mapping_strategies', ['employee_id']);

        // Strategy 1: Match by employee primary key if device user ID is numeric.
        if (in_array('employee_id', $strategies, true) && is_numeric($deviceUserId)) {
            $employee = Employee::find((int) $deviceUserId);
            if ($employee) {
                return $employee;
            }
        }

        // Strategy 2: Strict exact name match in same branch, only for currently unlinked employees.
        if (in_array('name_exact', $strategies, true) && !empty($deviceUserName)) {
            $candidates = Employee::query()
                ->where('name', trim($deviceUserName))
                ->where('branch_id', $device->branch_id)
                ->whereNull('device_id')
                ->whereNull('user_id_on_device')
                ->get();

            if ($candidates->count() === 1) {
                return $candidates->first();
            }

            if ($candidates->count() > 1) {
                Log::warning("Ambiguous name match for device user {$deviceUserId} ({$deviceUserName}) on device {$device->device_name}; skipping auto-link");
            }
        }

        return null;
    }

    /**
     * Ensure we don't overwrite an existing device-user mapping.
     */
    protected function canLinkEmployeeToDeviceUser(Employee $employee, AttendanceDevice $device, string $deviceUserId): bool
    {
        if (
            $employee->device_id !== null &&
            $employee->user_id_on_device !== null &&
            (
                (int) $employee->device_id !== (int) $device->id ||
                $this->normalizeDeviceUserId($employee->user_id_on_device) !== $deviceUserId
            )
        ) {
            return false;
        }

        return !Employee::where('device_id', $device->id)
            ->where('user_id_on_device', $deviceUserId)
            ->where('id', '!=', $employee->id)
            ->exists();
    }

    /**
     * Calculate working time and status
     */
    protected function calculateWorkingTime(AttendanceRecord $record, ?array $shiftRule = null): void
    {
        if (!$record->check_in) {
            return;
        }

        // Ensure attendance_date is just the date part (no time)
        $dateOnly = $record->attendance_date instanceof \Carbon\Carbon
            ? $record->attendance_date->toDateString()
            : date('Y-m-d', strtotime($record->attendance_date));

        $resolvedShiftRule = $shiftRule ?? $this->resolveShiftRuleForRecord($record, Carbon::parse($dateOnly));
        $this->applyWorkingTimeCalculation($record, $resolvedShiftRule);

        $record->save();
    }

    protected function resolveShiftRuleForEmployee(Employee $employee, Carbon $attendanceDate): array
    {
        $record = new AttendanceRecord();
        $record->setRelation('employee', $employee);

        return $this->resolveShiftRuleForRecord($record, $attendanceDate);
    }

    protected function resolveShiftRuleForRecord(AttendanceRecord $record, Carbon $attendanceDate): array
    {
        $employee = $record->employee;
        $defaultShiftStart = (string) config('payroll.shift_start', '09:00');
        $defaultGrace = (int) config('payroll.late_grace_minutes', 15);
        $shiftStart = !empty($employee?->shift_start_time)
            ? substr((string) $employee->shift_start_time, 0, 5)
            : $defaultShiftStart;

        $graceMinutes = $defaultGrace;
        $shiftName = trim((string) ($employee?->shift ?? ''));
        static $hasShiftTable = null;

        if ($shiftName !== '') {
            if ($hasShiftTable === null) {
                $hasShiftTable = DB::getSchemaBuilder()->hasTable('attendance_shifts');
            }

            if ($hasShiftTable) {
                $match = DB::table('attendance_shifts')
                    ->when(!empty($employee?->branch_id), function ($query) use ($employee) {
                        $query->where(function ($inner) use ($employee) {
                            $inner->where('branch_id', $employee->branch_id)
                                ->orWhereNull('branch_id');
                        });
                    })
                    ->whereRaw('LOWER(shift_name) = ?', [strtolower($shiftName)])
                    ->orderByDesc('is_default')
                    ->first();

                if ($match) {
                    $candidateStart = substr((string) $match->start_time, 0, 5);
                    if (preg_match('/^\d{2}:\d{2}$/', $candidateStart)) {
                        $shiftStart = $candidateStart;
                    }

                    $graceMinutes = (int) ($match->grace_period_minutes ?? $graceMinutes);
                }
            }
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $shiftStart)) {
            $shiftStart = $defaultShiftStart;
        }

        $shiftStartAt = Carbon::parse($attendanceDate->toDateString() . ' ' . $shiftStart);
        $workingHours = (float) ($employee?->working_hours ?? config('payroll.default_shift_hours', 8));
        $shiftEndAt = (clone $shiftStartAt)->addMinutes((int) round(max(0, $workingHours) * 60));

        return [
            'shift_start_at' => $shiftStartAt,
            'shift_end_at' => $shiftEndAt,
            'grace_minutes' => max(0, $graceMinutes),
        ];
    }

    /**
     * Sync all active devices
     */
    public function syncAllDevices(): array
    {
        $devices = AttendanceDevice::active()->get();
        $results = [];

        foreach ($devices as $device) {
            try {
                $userSyncResult = $this->syncDeviceUsers($device);
                $attendanceSyncResult = $this->syncAttendanceLogs($device);

                $results[] = [
                    'device_id' => $device->id,
                    'device_name' => $device->device_name,
                    'user_sync' => $userSyncResult,
                    'attendance_sync' => $attendanceSyncResult,
                ];
            } catch (Exception $e) {
                Log::error("Failed to sync device {$device->device_name}: " . $e->getMessage());
                $results[] = [
                    'device_id' => $device->id,
                    'device_name' => $device->device_name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Handle missing checkouts
     */
    public function handleMissingCheckouts(): void
    {
        try {
            Log::info("Handling missing checkouts");

            // Find records with missing checkout from yesterday or older
            $yesterday = Carbon::yesterday()->toDateString();
            $records = AttendanceRecord::where('is_checkout_missing', true)
                ->where('auto_checkout_applied', false)
                ->where('attendance_date', '<=', $yesterday)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->with('employee')
                ->get();

            foreach ($records as $record) {
                $checkIn = Carbon::parse($record->attendance_date . ' ' . $record->check_in);
                $autoCheckout = $checkIn->copy()->addHours(config('zkteco.default_auto_checkout_hours', 9));

                $record->applyAutoCheckout($autoCheckout->format('H:i:s'));
                $this->calculateWorkingTime($record);

                Log::info("Applied auto-checkout for employee {$record->employee_id} on {$record->attendance_date}");
            }

            Log::info("Handled missing checkouts for " . $records->count() . " records");
        } catch (Exception $e) {
            Log::error("Error handling missing checkouts: " . $e->getMessage());
        }
    }
}
