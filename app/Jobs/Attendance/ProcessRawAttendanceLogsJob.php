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

class ProcessRawAttendanceLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600;

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
                Log::error("Device with ID {$this->deviceId} not found for processing raw logs.");
                return;
            }

            Log::info("Job: Processing raw attendance logs for device {$device->device_name}");
            $syncService->processRawLogs($device);
        } catch (Exception $e) {
            Log::error("Job failed: Processing raw logs for device {$this->deviceId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Job permanently failed: Processing raw logs for device {$this->deviceId}: " . $exception->getMessage());
    }
}
