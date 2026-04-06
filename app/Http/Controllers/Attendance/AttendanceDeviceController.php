<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance\AttendanceDevice;
use App\Models\Attendance\AttendanceSyncLog;
use App\Models\Branch;
use App\Services\Attendance\ZKTecoService;
use App\Services\Attendance\AttendanceSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class AttendanceDeviceController extends Controller
{
    protected $zkService;
    protected $syncService;

    public function __construct(ZKTecoService $zkService, AttendanceSyncService $syncService)
    {
        $this->zkService = $zkService;
        $this->syncService = $syncService;
    }

    /**
     * Display a listing of devices
     */
    public function index()
    {
        $devices = AttendanceDevice::with('branch')->latest()->paginate(20);
        return view('attendance.devices.index', compact('devices'));
    }

    /**
     * Show the form for creating a new device
     */
    public function create()
    {
        $branches = Branch::where('status', 'active')->get();
        return view('attendance.devices.create', compact('branches'));
    }

    /**
     * Store a newly created device
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'device_name' => 'required|string|max:255',
            'device_serial_number' => 'nullable|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'password' => 'nullable|string',
            'com_key' => 'nullable|string',
            'sync_interval_minutes' => 'required|integer|min:1|max:60',
        ]);

        $validated['password'] = $validated['password'] ?? '0';
        $validated['com_key'] = $validated['com_key'] ?? '0';
        $validated['is_active'] = true;
        $validated['connection_status'] = 'unknown';

        $device = AttendanceDevice::create($validated);

        return redirect()->route('attendance.devices.index')
            ->with('success', 'Device added successfully! Please test the connection.');
    }

    /**
     * Show the form for editing the device
     */
    public function edit(AttendanceDevice $device)
    {
        $branches = Branch::where('status', 'active')->get();
        return view('attendance.devices.edit', compact('device', 'branches'));
    }

    /**
     * Update the device
     */
    public function update(Request $request, AttendanceDevice $device)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'device_name' => 'required|string|max:255',
            'device_serial_number' => 'nullable|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'password' => 'nullable|string',
            'com_key' => 'nullable|string',
            'sync_interval_minutes' => 'required|integer|min:1|max:60',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = $validated['password'] ?? '0';
        $validated['com_key'] = $validated['com_key'] ?? '0';

        $device->update($validated);

        return redirect()->route('attendance.devices.index')
            ->with('success', 'Device updated successfully!');
    }

    /**
     * Remove the device (soft delete)
     */
    public function destroy(AttendanceDevice $device)
    {
        $device->delete();

        return redirect()->route('attendance.devices.index')
            ->with('success', 'Device deleted successfully!');
    }

    /**
     * Test connection to device (AJAX)
     */
    public function testConnection(AttendanceDevice $device)
    {
        try {
            $result = $this->zkService->testConnection($device);

            return response()->json($result);
        } catch (Exception $e) {
            Log::error("Connection test error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'device_info' => null,
            ], 500);
        }
    }

    /**
     * Manual sync trigger
     */
    public function syncNow(Request $request, AttendanceDevice $device)
    {
        try {
            [$forceFullSync, $clearAfterFetch] = $this->resolveSyncOptions($request);
            $syncResult = $this->performDeviceSync($device, $forceFullSync, $clearAfterFetch);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $syncResult['message'],
                    'user_sync' => $syncResult['user_sync'],
                    'attendance_sync' => $syncResult['attendance_sync'],
                    'device_id' => $device->id,
                    'last_synced_at' => optional($device->fresh()->last_synced_at)?->format('Y-m-d H:i:s'),
                ]);
            }

            return redirect()->back()->with('success', $syncResult['message']);
        } catch (Exception $e) {
            Log::error("Manual sync error: " . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Manual multi-device sync trigger
     */
    public function syncAllNow(Request $request)
    {
        try {
            [$forceFullSync, $clearAfterFetch] = $this->resolveSyncOptions($request);

            $devices = AttendanceDevice::query()
                ->where('is_active', true)
                ->get();

            if ($devices->isEmpty()) {
                $message = 'No active devices found.';

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 404);
                }

                return redirect()->back()->with('error', $message);
            }

            $results = [];
            $totals = [
                'devices_total' => $devices->count(),
                'devices_success' => 0,
                'devices_failed' => 0,
                'users_fetched' => 0,
                'users_new' => 0,
                'users_updated' => 0,
                'attendance_fetched' => 0,
                'attendance_new' => 0,
                'attendance_duplicate' => 0,
            ];

            foreach ($devices as $device) {
                try {
                    $syncResult = $this->performDeviceSync($device, $forceFullSync, $clearAfterFetch);

                    $results[] = [
                        'device_id' => $device->id,
                        'device_name' => $device->device_name,
                        'success' => true,
                        'message' => $syncResult['message'],
                        'user_sync' => $syncResult['user_sync'],
                        'attendance_sync' => $syncResult['attendance_sync'],
                        'last_synced_at' => optional($device->fresh()->last_synced_at)?->format('Y-m-d H:i:s'),
                    ];

                    $totals['devices_success']++;
                    $totals['users_fetched'] += (int) ($syncResult['user_sync']['records_fetched'] ?? 0);
                    $totals['users_new'] += (int) ($syncResult['user_sync']['records_new'] ?? 0);
                    $totals['users_updated'] += (int) ($syncResult['user_sync']['records_updated'] ?? 0);
                    $totals['attendance_fetched'] += (int) ($syncResult['attendance_sync']['records_fetched'] ?? 0);
                    $totals['attendance_new'] += (int) ($syncResult['attendance_sync']['records_new'] ?? 0);
                    $totals['attendance_duplicate'] += (int) ($syncResult['attendance_sync']['records_duplicate'] ?? 0);
                } catch (Exception $e) {
                    $totals['devices_failed']++;

                    $results[] = [
                        'device_id' => $device->id,
                        'device_name' => $device->device_name,
                        'success' => false,
                        'message' => $e->getMessage(),
                        'last_synced_at' => optional($device->fresh()->last_synced_at)?->format('Y-m-d H:i:s'),
                    ];

                    Log::error("Manual sync-all device error for {$device->device_name}: " . $e->getMessage());
                }
            }

            $message = sprintf(
                'Sync all completed! Devices: %d success, %d failed. Users: %d fetched, %d new, %d updated. Attendance: %d fetched, %d new, %d duplicates.',
                $totals['devices_success'],
                $totals['devices_failed'],
                $totals['users_fetched'],
                $totals['users_new'],
                $totals['users_updated'],
                $totals['attendance_fetched'],
                $totals['attendance_new'],
                $totals['attendance_duplicate']
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => $totals['devices_success'] > 0,
                    'message' => $message,
                    'totals' => $totals,
                    'devices' => $results,
                ], $totals['devices_success'] > 0 ? 200 : 500);
            }

            if ($totals['devices_success'] > 0) {
                return redirect()->back()->with('success', $message);
            }

            return redirect()->back()->with('error', $message);
        } catch (Exception $e) {
            Log::error("Manual sync-all error: " . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync all failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Sync all failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve sync options from request with safety guards.
     */
    protected function resolveSyncOptions(Request $request): array
    {
        // Manual sync should backfill full device history unless explicitly disabled.
        $forceFullSync = $request->boolean('force_full_sync', true);

        // Never clear device logs unless explicitly enabled in config.
        $allowClearAfterFetch = (bool) config('zkteco.allow_clear_after_fetch', false);
        $clearAfterFetchRequested = $request->boolean('clear_after_fetch', false);
        $clearAfterFetch = $allowClearAfterFetch && $clearAfterFetchRequested;

        if ($clearAfterFetchRequested && !$allowClearAfterFetch) {
            Log::warning('clear_after_fetch was requested but is disabled by configuration');
        }

        return [$forceFullSync, $clearAfterFetch];
    }

    /**
     * Run a single device sync cycle.
     */
    protected function performDeviceSync(AttendanceDevice $device, bool $forceFullSync, bool $clearAfterFetch): array
    {
        // On first sync fetch all history. Manual sync can also force a full pull.
        $isFirstSync = is_null($device->last_synced_at);
        $from = ($isFirstSync || $forceFullSync) ? null : Carbon::parse($device->last_synced_at);

        $deviceData = $this->zkService->getUsersAndAttendance($device, $from, $clearAfterFetch);

        // Sync users
        $userResult = $this->syncService->syncDeviceUsers($device, $deviceData['users'] ?? collect([]));

        // Sync attendance
        $attendanceResult = $this->syncService->syncAttendanceLogs($device, $forceFullSync, $deviceData['logs'] ?? collect([]));

        $message = sprintf(
            'Sync completed! Users: %d fetched, %d new, %d updated. Attendance: %d fetched, %d new, %d duplicates.',
            $userResult['records_fetched'] ?? 0,
            $userResult['records_new'] ?? 0,
            $userResult['records_updated'] ?? 0,
            $attendanceResult['records_fetched'] ?? 0,
            $attendanceResult['records_new'] ?? 0,
            $attendanceResult['records_duplicate'] ?? 0
        );

        return [
            'message' => $message,
            'user_sync' => $userResult,
            'attendance_sync' => $attendanceResult,
        ];
    }

    /**
     * View sync logs
     */
    public function syncLogs(AttendanceDevice $device)
    {
        // Get all devices for filter dropdown
        $devices = AttendanceDevice::orderBy('device_name')->get();

        $logs = AttendanceSyncLog::where('device_id', $device->id)
            ->latest()
            ->paginate(50);

        return view('attendance.devices.sync-logs', compact('device', 'devices', 'logs'));
    }

    /**
     * Toggle device active status (AJAX)
     */
    public function toggleActive(AttendanceDevice $device)
    {
        $device->update(['is_active' => !$device->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $device->is_active,
            'message' => $device->is_active ? 'Device activated' : 'Device deactivated',
        ]);
    }
}
