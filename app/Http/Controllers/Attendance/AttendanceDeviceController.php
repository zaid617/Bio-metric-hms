<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance\AttendanceDevice;
use App\Models\Attendance\AttendanceSyncLog;
use App\Models\Branch;
use App\Services\Attendance\ZKTecoService;
use App\Services\Attendance\AttendanceSyncService;
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
    public function syncNow(AttendanceDevice $device)
    {
        try {
            // Sync users
            $userResult = $this->syncService->syncDeviceUsers($device);

            // Sync attendance
            $attendanceResult = $this->syncService->syncAttendanceLogs($device);

            $message = "Sync completed! Users: {$userResult['records_new']} new. Attendance: {$attendanceResult['records_new']} new logs.";

            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            Log::error("Manual sync error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
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
