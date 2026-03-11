<?php

namespace App\Jobs\Attendance;

use App\Models\Attendance\AttendanceDevice;
use App\Services\Attendance\AttendanceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncDeviceUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected $deviceId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $deviceId)
    {
        $this->deviceId = $deviceId;
    }

    /**
     * Execute the job.
     */
    public function handle(AttendanceSyncService $syncService): void
    {
        try {
            $device = AttendanceDevice::find($this->deviceId);

            if (!$device) {
                Log::error("Device with ID {$this->deviceId} not found for user sync.");
                return;
            }

            Log::info("Job: Syncing users for device {$device->device_name}");
            $syncService->syncDeviceUsers($device);
        } catch (Exception $e) {
            Log::error("Job failed: User sync for device {$this->deviceId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Job permanently failed: User sync for device {$this->deviceId}: " . $exception->getMessage());
    }
}
