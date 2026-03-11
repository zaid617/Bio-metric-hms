<?php

namespace App\Services\Attendance;

use App\Models\Attendance\AttendanceDevice;
use App\Models\Attendance\AttendanceRawLog;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Attendance\AttendanceSyncLog;
use App\Models\Employee;
use Carbon\Carbon;
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
    public function syncDeviceUsers(AttendanceDevice $device): array
    {
        $startedAt = now();
        $recordsFetched = 0;
        $recordsNew = 0;
        $recordsUpdated = 0;
        $errorMessage = null;
        $status = 'success';

        try {
            Log::info("Starting user sync for device: {$device->device_name}");

            // Fetch users from device
            $users = $this->zkService->getUsers($device);
            $recordsFetched = $users->count();

            if ($recordsFetched === 0) {
                $status = 'failed';
                $errorMessage = 'No users fetched from device';
            } else {
                foreach ($users as $userData) {
                    // Check if an employee is already linked to this device user
                    $employee = Employee::where('device_id', $device->id)
                        ->where('user_id_on_device', $userData['user_id_on_device'])
                        ->first();

                    if ($employee) {
                        // Update name if it changed on the device
                        if (!empty($userData['name']) && $employee->name !== $userData['name']) {
                            $employee->update(['name' => $userData['name']]);
                        }
                        $recordsUpdated++;
                    } else {
                        // Strategy 1: match by numeric employee ID
                        $matched = null;
                        if (is_numeric($userData['user_id_on_device'])) {
                            $matched = Employee::find((int) $userData['user_id_on_device']);
                        }

                        // Strategy 2: match by exact name, prefer same branch
                        if (!$matched && !empty($userData['name'])) {
                            $matched = Employee::where('name', $userData['name'])
                                ->where('branch_id', $device->branch_id)
                                ->first();
                            if (!$matched) {
                                $matched = Employee::where('name', $userData['name'])->first();
                            }
                        }

                        if ($matched) {
                            // Link existing employee to this device
                            $matched->update([
                                'device_id' => $device->id,
                                'user_id_on_device' => $userData['user_id_on_device'],
                            ]);
                            $recordsUpdated++;
                            Log::info("Linked existing employee {$matched->name} to device user {$userData['user_id_on_device']} on device {$device->device_name}");
                        } else {
                            // Create a new employee from this device user
                            Employee::create([
                                'name'              => !empty($userData['name']) ? $userData['name'] : "Device User {$userData['user_id_on_device']}",
                                'designation'       => 'Employee',
                                'branch_id'         => $device->branch_id,
                                'basic_salary'      => 0,
                                'shift'             => '',
                                'device_id'         => $device->id,
                                'user_id_on_device' => $userData['user_id_on_device'],
                            ]);
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
            'message' => $errorMessage ?? "Successfully synced {$recordsFetched} users",
        ];
    }

    /**
     * Sync attendance logs from device
     */
    public function syncAttendanceLogs(AttendanceDevice $device): array
    {
        $startedAt = now();
        $recordsFetched = 0;
        $recordsNew = 0;
        $recordsDuplicate = 0;
        $errorMessage = null;
        $status = 'success';

        try {
            Log::info("Starting attendance sync for device: {$device->device_name}");

            // On first sync (last_synced_at is null) fetch ALL historical logs from the device.
            // On subsequent syncs, only fetch logs since the last successful sync.
            $isFirstSync = is_null($device->last_synced_at);
            $from = $isFirstSync ? null : Carbon::parse($device->last_synced_at);

            if ($isFirstSync) {
                Log::info("First sync for device {$device->device_name} — fetching all historical logs");
            }

            $logs = $this->zkService->getAttendanceLogs($device, $from);
            $recordsFetched = $logs->count();

            if ($recordsFetched === 0) {
                Log::info("No new attendance logs for device {$device->device_name}");
                // Mark the device as synced even when empty so future syncs are incremental.
                $device->update(['last_synced_at' => now()]);
            } else {
                foreach ($logs as $logData) {
                    try {
                        // Try to insert, will fail silently on duplicate (unique constraint)
                        $rawLog = AttendanceRawLog::firstOrCreate(
                            [
                                'device_id' => $device->id,
                                'user_id_on_device' => $logData['user_id_on_device'],
                                'punch_time' => $logData['punch_time'],
                            ],
                            [
                                'device_user_uid' => $logData['uid'],
                                'punch_type' => $logData['punch_type'],
                                'verify_type' => $logData['verify_type'],
                                'work_code' => $logData['work_code'],
                                'is_processed' => false,
                            ]
                        );

                        if ($rawLog->wasRecentlyCreated) {
                            $recordsNew++;
                        } else {
                            $recordsDuplicate++;
                        }
                    } catch (Exception $e) {
                        // Duplicate entry, skip
                        $recordsDuplicate++;
                    }
                }

                // Update last synced time
                $device->update(['last_synced_at' => now()]);

                Log::info("Attendance sync completed for device {$device->device_name}: {$recordsNew} new, {$recordsDuplicate} duplicates");

                // Process raw logs (always process, not just when new records exist)
                $this->processRawLogs($device);
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
            'message' => $errorMessage ?? "Successfully synced {$recordsNew} new attendance logs",
        ];
    }

    /**
     * Process raw attendance logs into attendance records
     */
    public function processRawLogs(AttendanceDevice $device): void
    {
        try {
            Log::info("Processing raw attendance logs for device: {$device->device_name}");

            // Get unprocessed logs
            $unprocessedLogs = AttendanceRawLog::where('device_id', $device->id)
                ->where('is_processed', false)
                ->orderBy('punch_time', 'asc')
                ->get();

            if ($unprocessedLogs->isEmpty()) {
                Log::info("No unprocessed logs found for device {$device->device_name}");
                return;
            }

            Log::info("Found {$unprocessedLogs->count()} unprocessed logs for device {$device->device_name}");

            $processed = 0;
            $skipped = 0;

            foreach ($unprocessedLogs as $rawLog) {
                // Find employee directly by device and user_id_on_device
                $employee = Employee::where('device_id', $device->id)
                    ->where('user_id_on_device', $rawLog->user_id_on_device)
                    ->first();

                if (!$employee) {
                    Log::debug("Employee not found for device user_id_on_device: {$rawLog->user_id_on_device} on device {$device->device_name}");
                    $skipped++;
                    continue;
                }

                $attendanceDate = $rawLog->punch_time->toDateString();
                $punchTime = $rawLog->punch_time->format('H:i:s');

                // Find or create attendance record for this date
                $attendanceRecord = AttendanceRecord::firstOrNew([
                    'employee_id' => $employee->id,
                    'attendance_date' => $attendanceDate,
                ]);

                // Determine if this is a new record or existing
                $isNewRecord = !$attendanceRecord->exists;

                // First punch of the day = check-in
                if ($isNewRecord || empty($attendanceRecord->check_in)) {
                    $attendanceRecord->branch_id = $employee->branch_id;
                    $attendanceRecord->device_id = $device->id;
                    $attendanceRecord->check_in = $punchTime;
                    $attendanceRecord->check_in_raw_log_id = $rawLog->id;
                    $attendanceRecord->status = 'present';

                    Log::debug("Created check-in for employee {$employee->name} on {$attendanceDate} at {$punchTime}");
                } else {
                    // Subsequent punches update check-out (last punch = checkout)
                    $attendanceRecord->check_out = $punchTime;
                    $attendanceRecord->check_out_raw_log_id = $rawLog->id;

                    Log::debug("Updated check-out for employee {$employee->name} on {$attendanceDate} at {$punchTime}");
                }

                $attendanceRecord->save();

                // Calculate working time and determine status
                $this->calculateWorkingTime($attendanceRecord);

                // Mark raw log as processed
                $rawLog->markAsProcessed();
                $processed++;
            }

            Log::info("Processed {$processed} raw logs for device {$device->device_name}, skipped {$skipped} (unmapped users)");
        } catch (Exception $e) {
            Log::error("Error processing raw logs for device {$device->device_name}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Calculate working time and status
     */
    protected function calculateWorkingTime(AttendanceRecord $record): void
    {
        if (!$record->check_in) {
            return;
        }

        // Ensure attendance_date is just the date part (no time)
        $dateOnly = $record->attendance_date instanceof \Carbon\Carbon
            ? $record->attendance_date->toDateString()
            : date('Y-m-d', strtotime($record->attendance_date));

        $checkIn = Carbon::parse($dateOnly . ' ' . $record->check_in);

        if ($record->check_out) {
            $checkOut = Carbon::parse($dateOnly . ' ' . $record->check_out);

            // Handle night shift (checkout next day)
            if ($checkOut->lt($checkIn)) {
                $checkOut->addDay();
            }

            $record->total_working_minutes = $checkIn->diffInMinutes($checkOut);

            // Calculate overtime based on employee's standard working hours
            $employee = $record->employee;
            $standardMinutes = ($employee && $employee->working_hours)
                ? (float) $employee->working_hours * 60
                : 8 * 60;

            if ($record->total_working_minutes > $standardMinutes) {
                $record->overtime_minutes = (int) ($record->total_working_minutes - $standardMinutes);
            } else {
                $record->overtime_minutes = 0;
            }
        }

        // Detect late arrival: check check_in against configured shift start + grace
        $shiftStart   = config('payroll.shift_start', '09:00');
        $graceMinutes = (int) config('payroll.late_grace_minutes', 15);
        $deadline     = Carbon::parse($dateOnly . ' ' . $shiftStart)->addMinutes($graceMinutes);
        if ($checkIn->gt($deadline)) {
            $record->status = 'late';
        } else {
            $record->status = 'present';
        }

        // Check for missing checkout
        if (!$record->check_out) {
            $record->is_checkout_missing = true;
        }

        $record->save();
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
