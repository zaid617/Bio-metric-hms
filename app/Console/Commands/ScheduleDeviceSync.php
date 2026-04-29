<?php

namespace App\Console\Commands;

use App\Models\Attendance\AttendanceDevice;
use App\Services\Attendance\AttendanceSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class ScheduleDeviceSync extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attendance:schedule-sync';

    /**
     * The console command description.
     */
    protected $description = 'Scheduled cron job to sync attendance data from all active devices every 6 hours';

    protected $syncService;

    /**
     * Create a new command instance.
     */
    public function __construct(AttendanceSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startedAt = now();
        $this->info("Starting scheduled device sync at {$startedAt->format('Y-m-d H:i:s')}");
        Log::info("Scheduled device sync started at {$startedAt->format('Y-m-d H:i:s')}");

        try {
            // Get all active devices
            $devices = AttendanceDevice::active()->get();

            if ($devices->isEmpty()) {
                $this->warn('No active devices found for sync.');
                Log::warning('Scheduled device sync: No active devices found.');
                return 0;
            }

            $this->info("Found {$devices->count()} active device(s) to sync.");

            $devicesSynced = 0;
            $totalRecordsInserted = 0;
            $errors = [];

            // Sync each device with error handling
            foreach ($devices as $device) {
                try {
                    $this->line("Syncing device: {$device->device_name} (ID: {$device->id})");
                    Log::info("Syncing device: {$device->device_name} (ID: {$device->id})");

                    // Sync users
                    $userSyncResult = $this->syncService->syncDeviceUsers($device);
                    $userRecordsInserted = $userSyncResult['records_new'] ?? 0;

                    // Sync attendance logs
                    $attendanceSyncResult = $this->syncService->syncAttendanceLogs($device);
                    $attendanceRecordsInserted = $attendanceSyncResult['records_new'] ?? 0;

                    $deviceRecordsInserted = $userRecordsInserted + $attendanceRecordsInserted;
                    $totalRecordsInserted += $deviceRecordsInserted;
                    $devicesSynced++;

                    $this->line("  ✓ Device synced: {$userRecordsInserted} user records, {$attendanceRecordsInserted} attendance records inserted");
                    Log::info("Device {$device->device_name} synced: {$userRecordsInserted} user records, {$attendanceRecordsInserted} attendance records inserted");

                } catch (Exception $e) {
                    $errorMsg = "Device {$device->device_name} (ID: {$device->id}) sync failed: " . $e->getMessage();
                    $this->error("  ✗ {$errorMsg}");
                    Log::error($errorMsg);
                    $errors[] = $errorMsg;
                }
            }

            $endedAt = now();
            $duration = $endedAt->diffInSeconds($startedAt);

            // Summary
            $this->line('');
            $this->info("=== Scheduled Device Sync Summary ===");
            $this->info("Started: {$startedAt->format('Y-m-d H:i:s')}");
            $this->info("Ended: {$endedAt->format('Y-m-d H:i:s')}");
            $this->info("Duration: {$duration}s");
            $this->info("Devices synced: {$devicesSynced} / {$devices->count()}");
            $this->info("Total records inserted: {$totalRecordsInserted}");

            if (!empty($errors)) {
                $this->line('');
                $this->warn("Errors encountered ({count($errors)}):");
                foreach ($errors as $error) {
                    $this->error("  • {$error}");
                }
            }

            Log::info("Scheduled device sync completed at {$endedAt->format('Y-m-d H:i:s')} — Devices synced: {$devicesSynced}/{$devices->count()}, Total records inserted: {$totalRecordsInserted}, Duration: {$duration}s");

            return 0;

        } catch (Exception $e) {
            $endedAt = now();
            $errorMsg = "Scheduled device sync failed: " . $e->getMessage();
            $this->error("Critical error: {$errorMsg}");
            Log::error($errorMsg, ['exception' => $e]);
            return 1;
        }
    }
}
