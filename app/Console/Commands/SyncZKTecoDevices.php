<?php

namespace App\Console\Commands;

use App\Models\Attendance\AttendanceDevice;
use App\Services\Attendance\AttendanceSyncService;
use Illuminate\Console\Command;
use Exception;

class SyncZKTecoDevices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'zkteco:sync
                            {--device= : Specific device ID to sync}
                            {--handle-missing-checkouts : Handle missing checkouts}';

    /**
     * The console command description.
     */
    protected $description = 'Sync attendance data from ZKTeco devices';

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
        $this->info('Starting ZKTeco device sync...');

        try {
            // Handle missing checkouts if flag is set
            if ($this->option('handle-missing-checkouts')) {
                $this->info('Handling missing checkouts...');
                $this->syncService->handleMissingCheckouts();
                $this->info('Missing checkouts handled successfully.');
                return 0;
            }

            // Sync specific device if device option is provided
            if ($deviceId = $this->option('device')) {
                $device = AttendanceDevice::find($deviceId);

                if (!$device) {
                    $this->error("Device with ID {$deviceId} not found.");
                    return 1;
                }

                $this->info("Syncing device: {$device->device_name}");
                $this->syncDevice($device);
                return 0;
            }

            // Sync all active devices
            $devices = AttendanceDevice::active()->get();

            if ($devices->isEmpty()) {
                $this->warn('No active devices found.');
                return 0;
            }

            $this->info("Found {$devices->count()} active device(s).");

            foreach ($devices as $device) {
                $this->info("Syncing device: {$device->device_name}");
                $this->syncDevice($device);
            }

            $this->info('All devices synced successfully!');
            return 0;
        } catch (Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Sync a single device
     */
    protected function syncDevice(AttendanceDevice $device): void
    {
        try {
            // Sync users
            $this->line("  → Syncing users...");
            $userResult = $this->syncService->syncDeviceUsers($device);

            if ($userResult['success']) {
                $this->info("  ✓ Users synced: {$userResult['records_new']} new, {$userResult['records_updated']} updated");
            } else {
                $this->warn("  ✗ User sync failed: {$userResult['message']}");
            }

            // Sync attendance
            $this->line("  → Syncing attendance logs...");
            $attendanceResult = $this->syncService->syncAttendanceLogs($device);

            if ($attendanceResult['success']) {
                $this->info("  ✓ Attendance synced: {$attendanceResult['records_new']} new logs");
            } else {
                $this->warn("  ✗ Attendance sync failed: {$attendanceResult['message']}");
            }
        } catch (Exception $e) {
            $this->error("  ✗ Device sync failed: " . $e->getMessage());
        }
    }
}
