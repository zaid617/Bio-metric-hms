<?php

namespace App\Jobs\Attendance;

use App\Services\Attendance\AttendanceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateMissingCheckoutsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AttendanceSyncService $syncService): void
    {
        try {
            Log::info("Job: Handling missing checkouts");
            $syncService->handleMissingCheckouts();
            Log::info("Job: Missing checkouts handled successfully");
        } catch (Exception $e) {
            Log::error("Job failed: Handling missing checkouts: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Job permanently failed: Handling missing checkouts: " . $exception->getMessage());
    }
}
