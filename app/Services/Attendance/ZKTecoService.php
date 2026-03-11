<?php

namespace App\Services\Attendance;

use App\Models\Attendance\AttendanceDevice;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ZKTecoService
{
    protected $zk;
    protected $timeout = 30;

    /**
     * Connect to ZKTeco device
     */
    public function connect(AttendanceDevice $device): bool
    {
        try {
            $this->zk = new ZKTeco($device->ip_address, $device->port);

            Log::info("Attempting to connect to device: {$device->device_name} ({$device->ip_address}:{$device->port})");

            $connected = $this->zk->connect();

            if ($connected) {
                Log::info("Successfully connected to device: {$device->device_name}");

                // Update device connection status
                $device->update([
                    'connection_status' => 'online',
                ]);

                return true;
            }

            Log::warning("Failed to connect to device: {$device->device_name}");
            $device->update(['connection_status' => 'offline']);

            return false;
        } catch (Exception $e) {
            Log::error("Connection error to device {$device->device_name}: " . $e->getMessage());
            $device->update(['connection_status' => 'offline']);
            return false;
        }
    }

    /**
     * Disconnect from device
     */
    public function disconnect(): void
    {
        if ($this->zk) {
            try {
                $this->zk->disconnect();
                Log::info("Disconnected from ZKTeco device");
            } catch (Exception $e) {
                Log::error("Error disconnecting from device: " . $e->getMessage());
            }
        }
    }

    /**
     * Test connection to device with detailed diagnostics
     */
    public function testConnection(AttendanceDevice $device): array
    {
        $diagnostics = [];

        try {
            // Test 1: Check if IP is reachable (ping)
            Log::info("Testing network connectivity to {$device->ip_address}");
            $diagnostics['ping_test'] = $this->testPing($device->ip_address);

            // Test 2: Check if UDP port is accessible
            Log::info("Testing UDP port {$device->port} accessibility");
            $diagnostics['port_test'] = $this->testUdpPort($device->ip_address, $device->port);

            // Test 3: Attempt device connection
            Log::info("Attempting device connection");
            $connected = $this->connect($device);

            if (!$connected) {
                $errorMessage = 'Failed to connect to device. ';

                if (!$diagnostics['ping_test']['success']) {
                    $errorMessage .= 'Device is not reachable on the network. Check network routing and VPN connection. ';
                } else if (!$diagnostics['port_test']['success']) {
                    $errorMessage .= 'UDP port 4370 appears to be blocked. Check firewall settings. ';
                } else {
                    $errorMessage .= 'Device is reachable but not responding. Check device power and configuration. ';
                }

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'device_info' => null,
                    'diagnostics' => $diagnostics,
                ];
            }

            $deviceInfo = $this->getDeviceInfo($device);
            $this->disconnect();

            return [
                'success' => true,
                'message' => 'Successfully connected to device!',
                'device_info' => $deviceInfo,
                'diagnostics' => $diagnostics,
            ];
        } catch (Exception $e) {
            Log::error("Test connection failed for device {$device->device_name}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'device_info' => null,
                'diagnostics' => $diagnostics,
            ];
        }
    }

    /**
     * Test if IP is reachable (ping test)
     */
    private function testPing(string $ip): array
    {
        try {
            // For Windows, use fping or Test-Connection
            $output = [];
            $returnVar = 0;

            // Windows ping command with timeout of 3 seconds
            exec("ping -n 1 -w 3000 {$ip}", $output, $returnVar);

            $success = ($returnVar === 0);

            return [
                'success' => $success,
                'message' => $success ? 'Device is reachable on the network' : 'Device is not reachable (ping failed)',
                'details' => implode("\n", $output)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ping test failed: ' . $e->getMessage(),
                'details' => ''
            ];
        }
    }

    /**
     * Test if UDP port is accessible
     */
    private function testUdpPort(string $ip, int $port): array
    {
        try {
            // Create a UDP socket connection test
            $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

            if (!$socket) {
                return [
                    'success' => false,
                    'message' => 'Failed to create UDP socket',
                    'details' => socket_strerror(socket_last_error())
                ];
            }

            // Set timeout for socket operations
            $timeout = ['sec' => 5, 'usec' => 0];
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout);
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeout);

            // Try to send a test packet
            $testData = "TEST";
            $sent = @socket_sendto($socket, $testData, strlen($testData), 0, $ip, $port);

            socket_close($socket);

            if ($sent === false) {
                return [
                    'success' => false,
                    'message' => 'Failed to send data to UDP port (may be blocked by firewall)',
                    'details' => socket_strerror(socket_last_error())
                ];
            }

            return [
                'success' => true,
                'message' => 'UDP port is accessible (packet sent successfully)',
                'details' => "Sent {$sent} bytes to {$ip}:{$port}"
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'UDP port test failed: ' . $e->getMessage(),
                'details' => ''
            ];
        }
    }

    /**
     * Get all users from device
     */
    public function getUsers(AttendanceDevice $device): Collection
    {
        try {
            if (!$this->connect($device)) {
                return collect([]);
            }

            $users = $this->zk->getUser();
            $this->disconnect();

            if (!$users) {
                return collect([]);
            }

            // Convert array to collection and format data
            $collection = collect($users)->map(function ($user) {
                // Normalize user ID - ensure it's trimmed and consistent
                $userId = $user['userid'] ?? $user['uid'] ?? '';
                if (is_string($userId)) {
                    $userId = trim($userId);
                }

                return [
                    'uid' => $user['uid'] ?? null,
                    'user_id_on_device' => $userId,
                    'name' => $user['name'] ?? 'Unknown',
                    'privilege' => $user['role'] ?? 0,
                    'password' => $user['password'] ?? null,
                    'card_number' => $user['cardno'] ?? null,
                    'raw_data' => $user,
                ];
            });

            Log::info("Fetched " . $collection->count() . " users from device: {$device->device_name}");

            return $collection;
        } catch (Exception $e) {
            Log::error("Error fetching users from device {$device->device_name}: " . $e->getMessage());
            $this->disconnect();
            return collect([]);
        }
    }

    /**
     * Get attendance logs from device
     */
    public function getAttendanceLogs(AttendanceDevice $device, ?Carbon $from = null): Collection
    {
        try {
            if (!$this->connect($device)) {
                return collect([]);
            }

            $logs = $this->zk->getAttendance();
            $this->disconnect();

            if (!$logs) {
                return collect([]);
            }

            // Convert to collection and filter by date if provided
            $collection = collect($logs)->map(function ($log) {
                // Parse timestamp - can be string date or unix timestamp
                $punchTime = null;
                if (isset($log['timestamp'])) {
                    try {
                        if (is_numeric($log['timestamp'])) {
                            // Unix timestamp
                            $punchTime = Carbon::createFromTimestamp($log['timestamp']);
                        } else {
                            // String date
                            $punchTime = Carbon::parse($log['timestamp']);
                        }
                    } catch (Exception $e) {
                        Log::warning("Failed to parse timestamp: " . $log['timestamp']);
                        $punchTime = Carbon::now();
                    }
                } else {
                    $punchTime = Carbon::now();
                }

                // Clean and normalize user ID
                $userId = $log['id'] ?? $log['uid'] ?? '';
                if (is_string($userId)) {
                    $userId = trim($userId);
                }

                return [
                    'uid' => $log['uid'] ?? null,
                    'user_id_on_device' => $userId,
                    'punch_time' => $punchTime,
                    'punch_type' => $log['type'] ?? 0,
                    'verify_type' => $log['state'] ?? 0,
                    'work_code' => 0,
                    'raw_data' => $log,
                ];
            });

            // Filter by date if provided
            if ($from) {
                $collection = $collection->filter(function ($log) use ($from) {
                    return $log['punch_time']->greaterThanOrEqualTo($from);
                });
            }

            Log::info("Fetched " . $collection->count() . " attendance logs from device: {$device->device_name}");

            return $collection;
        } catch (Exception $e) {
            Log::error("Error fetching attendance logs from device {$device->device_name}: " . $e->getMessage());
            $this->disconnect();
            return collect([]);
        }
    }

    /**
     * Clear attendance logs from device (use with caution)
     */
    public function clearAttendanceLogs(AttendanceDevice $device): bool
    {
        try {
            if (!$this->connect($device)) {
                return false;
            }

            $result = $this->zk->clearAttendance();
            $this->disconnect();

            if ($result) {
                Log::warning("Cleared attendance logs from device: {$device->device_name}");
            }

            return $result;
        } catch (Exception $e) {
            Log::error("Error clearing attendance logs from device {$device->device_name}: " . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }

    /**
     * Get device information
     */
    public function getDeviceInfo(AttendanceDevice $device): array
    {
        try {
            if (!$this->zk) {
                if (!$this->connect($device)) {
                    return [];
                }
            }

            $serialNumber = $this->zk->serialNumber();
            $platform = $this->zk->platform();
            $fmVersion = $this->zk->fmVersion();
            $version = $this->zk->version();
            $osVersion = $this->zk->osVersion();

            return [
                'serial_number' => $serialNumber,
                'platform' => $platform,
                'firmware_version' => $fmVersion,
                'version' => $version,
                'os_version' => $osVersion,
                'device_name' => $this->zk->deviceName() ?? $device->device_name,
            ];
        } catch (Exception $e) {
            Log::error("Error getting device info from device {$device->device_name}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Set/Add user to device
     */
    public function setUser(AttendanceDevice $device, array $userData): bool
    {
        try {
            if (!$this->connect($device)) {
                return false;
            }

            $uid = $userData['uid'] ?? null;
            $userId = $userData['user_id'] ?? $uid;
            $name = $userData['name'] ?? 'User';
            $password = $userData['password'] ?? '';
            $privilege = $userData['privilege'] ?? 0;
            $cardNumber = $userData['card_number'] ?? 0;

            $result = $this->zk->setUser(
                $uid,
                $userId,
                $name,
                $password,
                $privilege,
                $cardNumber
            );

            $this->disconnect();

            if ($result) {
                Log::info("Successfully added/updated user on device {$device->device_name}: {$name} (UID: {$uid})");
            }

            return $result;
        } catch (Exception $e) {
            Log::error("Error setting user on device {$device->device_name}: " . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }

    /**
     * Enable device
     */
    public function enableDevice(AttendanceDevice $device): bool
    {
        try {
            if (!$this->connect($device)) {
                return false;
            }

            $result = $this->zk->enableDevice();
            $this->disconnect();

            return $result;
        } catch (Exception $e) {
            Log::error("Error enabling device {$device->device_name}: " . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }

    /**
     * Disable device
     */
    public function disableDevice(AttendanceDevice $device): bool
    {
        try {
            if (!$this->connect($device)) {
                return false;
            }

            $result = $this->zk->disableDevice();
            $this->disconnect();

            return $result;
        } catch (Exception $e) {
            Log::error("Error disabling device {$device->device_name}: " . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }
}
