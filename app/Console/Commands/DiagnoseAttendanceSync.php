<?php

namespace App\Console\Commands;

use App\Models\Attendance\AttendanceDevice;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Attendance\AttendanceSyncLog;
use App\Models\Employee;
use App\Services\Attendance\ZKTecoService;
use App\Services\Attendance\AttendanceSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DiagnoseAttendanceSync extends Command
{
    protected $signature = 'attendance:diagnose {device_id? : The device ID to diagnose}';
    protected $description = 'Diagnose why attendance records are not syncing from ZKTeco device';

    protected $zkService;
    protected $syncService;

    public function __construct(ZKTecoService $zkService, AttendanceSyncService $syncService)
    {
        parent::__construct();
        $this->zkService = $zkService;
        $this->syncService = $syncService;
    }

    public function handle()
    {
        $deviceId = $this->argument('device_id');

        if (!$deviceId) {
            $devices = AttendanceDevice::all();

            if ($devices->isEmpty()) {
                $this->error('No devices found in the database.');
                return 1;
            }

            $this->info('Available Devices:');
            $this->table(
                ['ID', 'Name', 'IP Address', 'Status', 'Last Synced'],
                $devices->map(fn($d) => [
                    $d->id,
                    $d->device_name,
                    $d->ip_address,
                    $d->connection_status ?? 'unknown',
                    $d->last_synced_at ? $d->last_synced_at->diffForHumans() : 'Never'
                ])
            );

            $deviceId = $this->ask('Enter the device ID to diagnose');
        }

        $device = AttendanceDevice::find($deviceId);

        if (!$device) {
            $this->error("Device with ID {$deviceId} not found.");
            return 1;
        }

        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║     Attendance Sync Diagnostics                            ║");
        $this->info("╚════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->info("Device: {$device->device_name}");
        $this->info("IP: {$device->ip_address}:{$device->port}");
        $this->info("Last Synced: " . ($device->last_synced_at ? $device->last_synced_at->format('Y-m-d H:i:s') : 'Never'));
        $this->newLine();

        $logs = collect([]);
        $unmappedCount = 0;
        $mappedCount = 0;

        // Check 1: Device Connection
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Check 1: Device Connection");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $connectionResult = $this->zkService->testConnection($device);

        if ($connectionResult['success']) {
            $this->info("✓ Device is connected and responsive");
        } else {
            $this->error("✗ Device connection failed: " . $connectionResult['message']);
            $this->warn("Fix the connection issue first. Run: php artisan zkteco:diagnose {$deviceId}");
            return 1;
        }
        $this->newLine();

        // Check 2: Employees linked to this device
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Check 2: Employees Linked to Device");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $linkedEmployees = Employee::where('device_id', $device->id)->get();

        $this->info("Employees linked to this device: " . $linkedEmployees->count());

        if ($linkedEmployees->isEmpty()) {
            $this->warn("⚠ No employees are linked to this device!");
            $this->warn("   Sync the device users first: run sync to auto-create/link employees.");
        } else {
            $this->info("✓ Employees are linked");
            $this->table(
                ['Employee ID', 'Name', 'Branch', 'Device User ID'],
                $linkedEmployees->take(10)->map(fn($e) => [
                    $e->id,
                    $e->name,
                    $e->branch_id,
                    $e->user_id_on_device,
                ])
            );
            if ($linkedEmployees->count() > 10) {
                $this->warn('... and ' . ($linkedEmployees->count() - 10) . ' more');
            }
        }
        $this->newLine();

        // Check 3: Fetch Attendance from Device
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Check 3: Fetching Attendance Logs from Device");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $this->info("Attempting to fetch attendance logs...");

        try {
            $logs = $this->zkService->getAttendanceLogs($device);

            if ($logs->count() > 0) {
                $this->info("✓ Successfully fetched {$logs->count()} attendance logs from device");

                // Show sample
                $this->newLine();
                $this->info("Sample logs (first 5):");
                $this->table(
                    ['User ID', 'Punch Time', 'Type', 'Verify'],
                    $logs->take(5)->map(fn($l) => [
                        $l['user_id_on_device'],
                        $l['punch_time']->format('Y-m-d H:i:s'),
                        $l['punch_type'],
                        $l['verify_type']
                    ])
                );
            } else {
                $this->warn("⚠ No attendance logs found on device");
                $this->warn("Possible reasons:");
                $this->warn("  • No one has punched in/out on the device");
                $this->warn("  • Attendance logs have been cleared from device");
                $this->warn("  • All logs have already been synced");
            }
        } catch (\Exception $e) {
            $this->error("✗ Failed to fetch attendance logs: " . $e->getMessage());
            $this->error("Error details: " . $e->getTraceAsString());
        }
        $this->newLine();

        // Check 4: Mapping coverage for fetched logs
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Check 4: Device Punch Mapping Coverage");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        if ($logs instanceof Collection && $logs->isNotEmpty()) {
            $employeesByDeviceUserId = collect([]);
            $employeesByNumericUserId = collect([]);

            foreach ($linkedEmployees as $employee) {
                $normalizedId = trim((string) $employee->user_id_on_device);
                if ($normalizedId === '') {
                    continue;
                }

                if (!$employeesByDeviceUserId->has($normalizedId)) {
                    $employeesByDeviceUserId->put($normalizedId, $employee->id);
                }

                if (is_numeric($normalizedId)) {
                    $numericVariant = (string) ((int) $normalizedId);
                    if ($numericVariant !== '' && !$employeesByNumericUserId->has($numericVariant)) {
                        $employeesByNumericUserId->put($numericVariant, $employee->id);
                    }
                }
            }

            foreach ($logs as $log) {
                $userIdOnDevice = trim((string) ($log['user_id_on_device'] ?? ''));
                if ($userIdOnDevice === '') {
                    $unmappedCount++;
                    continue;
                }

                $mapped = $employeesByDeviceUserId->has($userIdOnDevice);

                if (!$mapped && is_numeric($userIdOnDevice)) {
                    $mapped = $employeesByNumericUserId->has((string) ((int) $userIdOnDevice));
                }

                if ($mapped) {
                    $mappedCount++;
                } else {
                    $unmappedCount++;
                }
            }

            $this->info("Mapped punches: {$mappedCount}");
            $this->info("Unmapped punches: {$unmappedCount}");

            if ($unmappedCount > 0) {
                $this->warn("⚠ Some device punches cannot be matched to linked employees");
            } else {
                $this->info("✓ All fetched punches can be matched to linked employees");
            }
        } else {
            $this->warn("⚠ No fetched punches available to evaluate mapping coverage");
        }
        $this->newLine();

        // Check 5: Attendance Records
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Check 5: Attendance Records");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $totalRecords = AttendanceRecord::where('device_id', $device->id)->count();
        $todayRecords = AttendanceRecord::where('device_id', $device->id)
            ->whereDate('attendance_date', today())
            ->count();
        $thisMonthRecords = AttendanceRecord::where('device_id', $device->id)
            ->whereMonth('attendance_date', now()->month)
            ->whereYear('attendance_date', now()->year)
            ->count();

        $this->info("Total attendance records: {$totalRecords}");
        $this->info("Today's records: {$todayRecords}");
        $this->info("This month's records: {$thisMonthRecords}");

        if ($totalRecords === 0) {
            $this->error("✗ No attendance records found!");
            $this->warn("This is the main issue - no attendance is being recorded.");
        } else {
            $this->info("✓ Attendance records exist");

            // Show recent records
            $recent = AttendanceRecord::where('device_id', $device->id)
                ->with('employee')
                ->latest('attendance_date')
                ->take(5)
                ->get();

            if ($recent->count() > 0) {
                $this->newLine();
                $this->info("Recent attendance records:");
                $this->table(
                    ['Date', 'Employee', 'Check In', 'Check Out', 'Status'],
                    $recent->map(fn($r) => [
                        $r->attendance_date,
                        $r->employee->name ?? 'N/A',
                        $r->check_in ?? '-',
                        $r->check_out ?? '-',
                        $r->status
                    ])
                );
            }
        }
        $this->newLine();

        // Check 6: Sync Logs
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Check 6: Recent Sync History");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $recentSyncs = AttendanceSyncLog::where('device_id', $device->id)
            ->where('sync_type', 'attendance')
            ->latest()
            ->take(5)
            ->get();

        if ($recentSyncs->isEmpty()) {
            $this->warn("⚠ No sync history found for attendance");
            $this->warn("   Attendance has never been synced from this device");
        } else {
            $this->table(
                ['Date', 'Status', 'Fetched', 'New', 'Duplicates', 'Error'],
                $recentSyncs->map(fn($s) => [
                    $s->created_at->format('Y-m-d H:i:s'),
                    $s->status,
                    $s->records_fetched,
                    $s->records_new,
                    $s->records_duplicate,
                    $s->error_message ? substr($s->error_message, 0, 50) . '...' : '-'
                ])
            );

            $failed = $recentSyncs->where('status', 'failed');
            if ($failed->count() > 0) {
                $this->error("✗ {$failed->count()} recent sync(s) failed!");
                $this->newLine();
                foreach ($failed as $sync) {
                    $this->error("Error: " . $sync->error_message);
                }
            }
        }
        $this->newLine();

        // Summary and Recommendations
        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║     Summary & Recommendations                              ║");
        $this->info("╚════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $hasIssues = false;

        if ($linkedEmployees->isEmpty()) {
            $hasIssues = true;
            $this->error("Issue 1: No employees are linked to this device");
            $this->warn("Solution: Link employees to this device (device_id + user_id_on_device)");
            $this->newLine();
        }

        if (($logs instanceof Collection ? $logs->count() : 0) === 0) {
            $hasIssues = true;
            $this->error("Issue 2: No attendance logs were fetched from the device");
            $this->warn("Solution: Verify device has punches and rerun sync");
            $this->warn("Command: php artisan zkteco:sync --device={$deviceId}");
            $this->newLine();
        }

        if ($unmappedCount > 0) {
            $hasIssues = true;
            $this->error("Issue 3: {$unmappedCount} fetched punches are not mapped to employees");
            $this->warn("Solution: Ensure employee user_id_on_device values match device user IDs");
            $this->newLine();
        }

        if ($totalRecords === 0 && $mappedCount > 0) {
            $hasIssues = true;
            $this->error("Issue 4: Mapped punches exist but no attendance records were created");
            $this->warn("Solution: Run sync and inspect logs for failures");
            $this->newLine();
        }

        if (!$hasIssues) {
            $this->info("✓ Everything looks good!");
            $this->info("Attendance is syncing properly from the device.");
            $this->newLine();
            $this->info("To sync new attendance:");
            $this->info("  php artisan zkteco:sync --device={$deviceId}");
        } else {
            $this->warn("Follow the recommendations above to fix the issues.");
        }

        return $hasIssues ? 1 : 0;
    }
}
