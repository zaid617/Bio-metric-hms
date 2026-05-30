<?php

namespace App\Services\Attendance;

use App\Models\Attendance\AttendanceDevice;
use Mithun\PhpZkteco\Libs\ZKTeco as ZKTecoLib;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ZKTecoService
{
    protected ?ZKTecoLib $zk = null;
    protected int $timeout = 30;
    protected string $protocolUsed = 'udp';

    public function __construct()
    {
        $this->timeout = max(1, (int) config('zkteco.connection_timeout', 30));
        $this->configureVendorLogger();
    }

    protected function configureVendorLogger(): void
    {
        if (defined('ZK_LIB_LOG')) {
            return;
        }

        $logPath = (string) config('zkteco.lib_log_path', storage_path('logs/zkteco/error.log'));

        if ($logPath === '') {
            $logPath = storage_path('logs/zkteco/error.log');
        }

        $logDir = dirname($logPath);

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        if (!file_exists($logPath)) {
            @touch($logPath);
        }

        define('ZK_LIB_LOG', $logPath);
    }

    protected function normalizeDeviceUserId($value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(str_replace("\0", '', (string) $value));
    }

    /**
     * Resolve the integer CommKey for a device (falls back to config default).
     */
    protected function resolvePassword(AttendanceDevice $device): int
    {
        $pw = $device->password;

        if ($pw === null) {
            return (int) config('zkteco.default_password', 0);
        }

        return (int) $pw;
    }

    /**
     * Create a ZKTecoLib instance for the given protocol, throwing on TCP failure.
     */
    protected function makeClient(string $ip, int $port, string $protocol, int $password): ZKTecoLib
    {
        return new ZKTecoLib($ip, $port, false, $this->timeout, $password, $protocol);
    }

    /**
     * Connect to ZKTeco device, honouring per-device protocol override or auto-falling back TCP→UDP.
     */
    public function connect(AttendanceDevice $device): bool
    {
        try {
            if (!function_exists('socket_create')) {
                throw new \RuntimeException('PHP sockets extension is not enabled. Enable php-sockets on the server.');
            }

            $ip       = $device->ip_address;
            $port     = (int) $device->port;
            $password = $this->resolvePassword($device);
            $override = $device->protocol ? strtolower($device->protocol) : null;

            Log::info("Attempting to connect to device: {$device->device_name} ({$ip}:{$port})");

            if ($override !== null) {
                // Device has a pinned protocol — use it directly.
                $this->zk            = $this->makeClient($ip, $port, $override, $password);
                $this->protocolUsed  = $override;
            } else {
                // Auto-detect: try TCP first, fall back to UDP.
                $defaultProtocol = strtolower((string) config('zkteco.default_protocol', 'tcp'));
                $fallback        = ($defaultProtocol === 'tcp') ? 'udp' : 'tcp';
                $autoFallback    = (bool) config('zkteco.auto_protocol_fallback', true);

                try {
                    $this->zk           = $this->makeClient($ip, $port, $defaultProtocol, $password);
                    $this->protocolUsed = $defaultProtocol;
                } catch (Exception $e) {
                    if (!$autoFallback) {
                        throw $e;
                    }

                    Log::info("TCP connection failed for {$device->device_name}, falling back to {$fallback}: " . $e->getMessage());
                    $this->zk           = $this->makeClient($ip, $port, $fallback, $password);
                    $this->protocolUsed = $fallback;
                }
            }

            $connected = $this->zk->connect();

            if ($connected) {
                Log::info("Successfully connected to device: {$device->device_name} (protocol: {$this->protocolUsed})");

                $device->update(['connection_status' => 'online']);

                return true;
            }

            Log::warning("Failed to connect to device: {$device->device_name}");
            $device->update(['connection_status' => 'offline']);
            $this->zk = null;

            return false;
        } catch (Exception $e) {
            Log::error("Connection error to device {$device->device_name}: " . $e->getMessage());
            $device->update(['connection_status' => 'offline']);
            $this->zk = null;
            return false;
        } catch (\Throwable $e) {
            Log::error("Connection error to device {$device->device_name}: " . $e->getMessage());
            $device->update(['connection_status' => 'offline']);
            $this->zk = null;
            return false;
        }
    }

    public function disconnect(): void
    {
        if ($this->zk) {
            try {
                $this->zk->disconnect();
                Log::info("Disconnected from ZKTeco device");
            } catch (Exception $e) {
                Log::error("Error disconnecting from device: " . $e->getMessage());
            }

            $this->zk = null;
        }
    }

    /**
     * Test connection to device with detailed diagnostics.
     *
     * Response shape (backward-compat):
     *   device_info.protocol_used       — "tcp" or "udp"
     *   diagnostics.tcp_port_test       — TCP-specific port test
     *   diagnostics.udp_port_test       — UDP-specific port test
     *   diagnostics.port_test           — preserved alias (whichever succeeded, or last failed)
     */
    public function testConnection(AttendanceDevice $device): array
    {
        $diagnostics = [];

        try {
            if (!function_exists('socket_create')) {
                return [
                    'success'     => false,
                    'message'     => 'PHP sockets extension is not enabled on this server. Install/enable php-sockets first.',
                    'device_info' => null,
                    'diagnostics' => [
                        'runtime' => [
                            'success' => false,
                            'message' => 'Missing sockets extension',
                            'details' => 'socket_create() is unavailable.',
                        ],
                    ],
                ];
            }

            Log::info("Testing network connectivity to {$device->ip_address}");
            $diagnostics['ping_test'] = $this->testPing($device->ip_address);

            Log::info("Testing TCP port {$device->port} accessibility");
            $diagnostics['tcp_port_test'] = $this->testTcpPort($device->ip_address, $device->port);

            Log::info("Testing UDP port {$device->port} accessibility");
            $diagnostics['udp_port_test'] = $this->testUdpPort($device->ip_address, $device->port);

            // Backward-compat alias — whichever port test succeeded, else the UDP result.
            $diagnostics['port_test'] = $diagnostics['tcp_port_test']['success']
                ? $diagnostics['tcp_port_test']
                : $diagnostics['udp_port_test'];

            Log::info("Attempting device connection");
            $connected = $this->connect($device);

            if (!$connected) {
                $tcpBlocked = !$diagnostics['tcp_port_test']['success'];
                $udpBlocked = !$diagnostics['udp_port_test']['success'];

                if (!$diagnostics['ping_test']['success']) {
                    $errorMessage = 'Failed to connect to device. Device is not reachable on the network. Check network routing and VPN connection.';
                } elseif ($tcpBlocked && $udpBlocked) {
                    $errorMessage = "Failed to connect to device. Both TCP and UDP on port {$device->port} appear to be blocked. Check firewall settings.";
                } else {
                    $errorMessage = 'Failed to connect to device. Device is reachable but not responding. Check device power and configuration.';
                }

                return [
                    'success'     => false,
                    'message'     => $errorMessage,
                    'device_info' => null,
                    'diagnostics' => $diagnostics,
                ];
            }

            $deviceInfo                  = $this->getDeviceInfo($device);
            $deviceInfo['protocol_used'] = $this->protocolUsed;

            $this->disconnect();

            return [
                'success'     => true,
                'message'     => 'Successfully connected to device!',
                'device_info' => $deviceInfo,
                'diagnostics' => $diagnostics,
            ];
        } catch (Exception $e) {
            Log::error("Test connection failed for device {$device->device_name}: " . $e->getMessage());

            return [
                'success'     => false,
                'message'     => 'Connection test failed: ' . $e->getMessage(),
                'device_info' => null,
                'diagnostics' => $diagnostics,
            ];
        }
    }

    private function testPing(string $ip): array
    {
        try {
            $output    = [];
            $returnVar = 0;

            exec($this->buildPingCommand($ip) . ' 2>&1', $output, $returnVar);

            $success = ($returnVar === 0);
            $details = implode("\n", $output);
            $message = $success
                ? 'Device is reachable on the network'
                : 'Device is not reachable (ping failed or command unavailable on this server)';

            if (!$success && stripos($details, 'not found') !== false) {
                $message = 'Ping command is unavailable on this server. Connection tests will rely on UDP/device checks.';
            }

            return [
                'success' => $success,
                'message' => $message,
                'details' => $details,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ping test failed: ' . $e->getMessage(),
                'details' => '',
            ];
        }
    }

    private function testTcpPort(string $ip, int $port): array
    {
        try {
            if (!function_exists('socket_create')) {
                return [
                    'success' => false,
                    'message' => 'PHP sockets extension is not enabled',
                    'details' => 'socket_create() is unavailable on this server.',
                ];
            }

            $timeout = min($this->timeout, 10);
            $socket  = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

            if (!$socket) {
                return [
                    'success' => false,
                    'message' => 'Failed to create TCP socket',
                    'details' => socket_strerror(socket_last_error()),
                ];
            }

            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeout, 'usec' => 0]);
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $timeout, 'usec' => 0]);

            $connected = @socket_connect($socket, $ip, $port);
            socket_close($socket);

            if ($connected) {
                return [
                    'success' => true,
                    'message' => 'TCP port is accessible',
                    'details' => "Connected to {$ip}:{$port} via TCP",
                ];
            }

            return [
                'success' => false,
                'message' => 'TCP port is not accessible (may be blocked by firewall)',
                'details' => socket_strerror(socket_last_error()),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'TCP port test failed: ' . $e->getMessage(),
                'details' => '',
            ];
        }
    }

    private function testUdpPort(string $ip, int $port): array
    {
        try {
            if (!function_exists('socket_create')) {
                return [
                    'success' => false,
                    'message' => 'PHP sockets extension is not enabled',
                    'details' => 'socket_create() is unavailable on this server.',
                ];
            }

            $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

            if (!$socket) {
                return [
                    'success' => false,
                    'message' => 'Failed to create UDP socket',
                    'details' => socket_strerror(socket_last_error()),
                ];
            }

            $timeout = ['sec' => min($this->timeout, 10), 'usec' => 0];
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout);
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeout);

            $testData = 'TEST';
            $sent     = @socket_sendto($socket, $testData, strlen($testData), 0, $ip, $port);

            if ($sent === false) {
                $errorCode    = socket_last_error($socket);
                $errorMessage = socket_strerror($errorCode);
                socket_close($socket);

                return [
                    'success' => false,
                    'message' => 'Failed to send data to UDP port (may be blocked by firewall)',
                    'details' => $errorMessage,
                ];
            }

            socket_close($socket);

            return [
                'success' => true,
                'message' => 'UDP port is accessible (packet sent successfully)',
                'details' => "Sent {$sent} bytes to {$ip}:{$port}",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'UDP port test failed: ' . $e->getMessage(),
                'details' => '',
            ];
        }
    }

    private function buildPingCommand(string $ip): string
    {
        $escapedIp = escapeshellarg($ip);

        if (PHP_OS_FAMILY === 'Windows') {
            return "ping -n 1 -w 3000 {$escapedIp}";
        }

        return "ping -c 1 -W 3 {$escapedIp}";
    }

    public function getUsers(AttendanceDevice $device): Collection
    {
        try {
            if (!$this->connect($device)) {
                return collect([]);
            }

            $users = $this->zk->getUsers();
            $this->disconnect();

            $collection = $this->mapDeviceUsers($users);

            Log::info("Fetched " . $collection->count() . " users from device: {$device->device_name}");

            return $collection;
        } catch (Exception $e) {
            Log::error("Error fetching users from device {$device->device_name}: " . $e->getMessage());
            $this->disconnect();
            return collect([]);
        }
    }

    public function getUsersAndAttendance(AttendanceDevice $device, ?Carbon $from = null, bool $clearAfterFetch = false): array
    {
        try {
            if (!$this->connect($device)) {
                return [
                    'users' => collect([]),
                    'logs'  => collect([]),
                ];
            }

            $users = $this->zk->getUsers();
            $logs  = $this->zk->getAttendances();

            // getAttendances() returns false on unrecoverable corruption — treat as empty.
            if ($logs === false) {
                $logs = [];
            }

            if ($clearAfterFetch && !empty($logs)) {
                try {
                    $cleared = $this->zk->clearAttendance();

                    if ($cleared) {
                        Log::info("Cleared attendance logs after fetch for device: {$device->device_name}");
                    } else {
                        Log::warning("Failed to clear attendance logs after fetch for device: {$device->device_name}");
                    }
                } catch (Exception $e) {
                    Log::warning("Non-fatal error clearing attendance logs after fetch for device {$device->device_name}: " . $e->getMessage());
                }
            }

            $this->disconnect();

            $userCollection = $this->mapDeviceUsers($users);
            $logCollection  = $this->mapAttendanceLogs($logs, $from);

            Log::info("Fetched " . $userCollection->count() . " users from device: {$device->device_name}");
            Log::info("Fetched " . $logCollection->count() . " attendance logs from device: {$device->device_name}");

            return [
                'users' => $userCollection,
                'logs'  => $logCollection,
            ];
        } catch (Exception $e) {
            Log::error("Error fetching users and attendance from device {$device->device_name}: " . $e->getMessage());
            $this->disconnect();

            return [
                'users' => collect([]),
                'logs'  => collect([]),
            ];
        }
    }

    protected function mapDeviceUsers($users): Collection
    {
        if (!$users) {
            return collect([]);
        }

        return collect($users)->map(function ($user) {
            $userId = $this->normalizeDeviceUserId($user['user_id'] ?? $user['uid'] ?? '');

            return [
                'uid'              => $user['uid'] ?? null,
                'user_id_on_device' => $userId,
                'name'             => $user['name'] ?? 'Unknown',
                'privilege'        => $user['role'] ?? 0,
                'password'         => $user['password'] ?? null,
                'card_number'      => $user['card_no'] ?? null,
                'raw_data'         => $user,
            ];
        });
    }

    public function getAttendanceLogs(AttendanceDevice $device, ?Carbon $from = null): Collection
    {
        try {
            if (!$this->connect($device)) {
                return collect([]);
            }

            $logs = $this->zk->getAttendances();
            $this->disconnect();

            if ($logs === false) {
                $logs = [];
            }

            $collection = $this->mapAttendanceLogs($logs, $from);

            Log::info("Fetched " . $collection->count() . " attendance logs from device: {$device->device_name}");

            return $collection;
        } catch (Exception $e) {
            Log::error("Error fetching attendance logs from device {$device->device_name}: " . $e->getMessage());
            $this->disconnect();
            return collect([]);
        }
    }

    protected function mapAttendanceLogs($logs, ?Carbon $from = null): Collection
    {
        if (!$logs) {
            return collect([]);
        }

        $collection = collect($logs)->map(function ($log) {
            $punchTime = null;

            // New library field: 'record_time' (formatted string "Y-m-d H:i:s")
            $rawTime = $log['record_time'] ?? $log['timestamp'] ?? null;

            if ($rawTime !== null) {
                try {
                    if (is_numeric($rawTime)) {
                        $punchTime = Carbon::createFromTimestamp($rawTime);
                    } else {
                        $punchTime = Carbon::parse($rawTime);
                    }
                } catch (Exception $e) {
                    Log::warning("Failed to parse timestamp: " . $rawTime);
                    $punchTime = Carbon::now();
                }
            } else {
                $punchTime = Carbon::now();
            }

            // New library field: 'user_id' (integer); old was 'id' (string)
            $userId = $this->normalizeDeviceUserId($log['user_id'] ?? $log['id'] ?? $log['uid'] ?? '');

            return [
                'uid'              => $log['uid'] ?? null,
                'user_id_on_device' => $userId,
                'punch_time'       => $punchTime,
                'punch_type'       => $log['type'] ?? 0,
                'verify_type'      => $log['state'] ?? 0,
                'work_code'        => 0,
                'raw_data'         => $log,
            ];
        });

        if ($from) {
            $collection = $collection->filter(function ($log) use ($from) {
                return $log['punch_time']->greaterThanOrEqualTo($from);
            });
        }

        return $collection;
    }

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

    public function getDeviceInfo(AttendanceDevice $device): array
    {
        try {
            if (!$this->zk) {
                if (!$this->connect($device)) {
                    return [];
                }
            }

            $serialNumber = $this->zk->serialNumber();
            $platform     = $this->zk->platform();
            $fmVersion    = $this->zk->fmVersion();
            $version      = $this->zk->version();
            $osVersion    = $this->zk->osVersion();

            return [
                'serial_number'    => $serialNumber,
                'platform'         => $platform,
                'firmware_version' => $fmVersion,
                'version'          => $version,
                'os_version'       => $osVersion,
                'device_name'      => $this->zk->deviceName() ?? $device->device_name,
            ];
        } catch (Exception $e) {
            Log::error("Error getting device info from device {$device->device_name}: " . $e->getMessage());
            return [];
        }
    }

    public function setUser(AttendanceDevice $device, array $userData): bool
    {
        try {
            if (!$this->connect($device)) {
                return false;
            }

            $uid        = $userData['uid'] ?? null;
            $userId     = $userData['user_id'] ?? $uid;
            $name       = $userData['name'] ?? 'User';
            $password   = $userData['password'] ?? '';
            $privilege  = $userData['privilege'] ?? 0;
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
