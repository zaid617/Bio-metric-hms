<?php

namespace App\Console\Commands;

use App\Models\Attendance\AttendanceDevice;
use App\Services\Attendance\AttendanceSyncService;
use Illuminate\Console\Command;

class ProcessAttendanceLogs extends Command
{
    protected $signature = 'attendance:process-logs {--device= : Process logs for specific device ID}';
    protected $description = 'Process raw attendance logs into attendance records';

    protected $syncService;

    public function __construct(AttendanceSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle()
    {
        $deviceId = $this->option('device');

        if ($deviceId) {
            $device = AttendanceDevice::find($deviceId);

            if (!$device) {
                $this->error("Device with ID {$deviceId} not found.");
                return 1;
            }

            $this->processDevice($device);
        } else {
            // Process all active devices
            $devices = AttendanceDevice::where('is_active', true)->get();

            if ($devices->isEmpty()) {
                $this->warn('No active devices found.');
                return 1;
            }

            $this->info("Processing logs for {$devices->count()} devices...");

            foreach ($devices as $device) {
                $this->processDevice($device);
            }
        }

        $this->info("✓ Processing complete!");
        return 0;
    }

    private function processDevice(AttendanceDevice $device)
    {
        $this->info("Processing device: {$device->device_name}");

        try {
            $this->syncService->processRawLogs($device);
            $this->info("✓ {$device->device_name}: Processed successfully");
        } catch (\Exception $e) {
            $this->error("✗ {$device->device_name}: " . $e->getMessage());
        }

        $this->newLine();
    }
}
