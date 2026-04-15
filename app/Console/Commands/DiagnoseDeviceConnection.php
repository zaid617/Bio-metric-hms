<?php

namespace App\Console\Commands;

use App\Models\Attendance\AttendanceDevice;
use App\Services\Attendance\ZKTecoService;
use Illuminate\Console\Command;

class DiagnoseDeviceConnection extends Command
{
    protected $signature = 'zkteco:diagnose {device_id? : The device ID to diagnose}';
    protected $description = 'Diagnose ZKTeco device connection issues with detailed network tests';

    protected $zkService;

    public function __construct(ZKTecoService $zkService)
    {
        parent::__construct();
        $this->zkService = $zkService;
    }

    public function handle()
    {
        $deviceId = $this->argument('device_id');

        if (!$deviceId) {
            // Show all devices and let user select
            $devices = AttendanceDevice::all();

            if ($devices->isEmpty()) {
                $this->error('No devices found in the database.');
                return 1;
            }

            $this->info('Available Devices:');
            $this->table(
                ['ID', 'Name', 'IP Address', 'Port', 'Status'],
                $devices->map(fn($d) => [
                    $d->id,
                    $d->device_name,
                    $d->ip_address,
                    $d->port,
                    $d->connection_status ?? 'unknown'
                ])
            );

            $deviceId = $this->ask('Enter the device ID to diagnose');
        }

        $device = AttendanceDevice::find($deviceId);

        if (!$device) {
            $this->error("Device with ID {$deviceId} not found.");
            return 1;
        }

        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║     ZKTeco Device Connection Diagnostics                  ║");
        $this->info("╚════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->info("Device: {$device->device_name}");
        $this->info("IP Address: {$device->ip_address}");
        $this->info("Port: {$device->port}");
        $this->newLine();

        // Test 1: Network Reachability (Ping)
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Test 1: Network Reachability (Ping Test)");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $pingBar = $this->output->createProgressBar(1);
        $pingBar->start();

        $pingResult = $this->testPing($device->ip_address);
        $pingBar->finish();
        $this->newLine();

        if ($pingResult['success']) {
            $this->info("✓ PASS: {$pingResult['message']}");
        } else {
            $this->error("✗ FAIL: {$pingResult['message']}");
            $this->warn("Troubleshooting Tips:");
            $this->warn("  • Check if the device is powered on");
            $this->warn("  • Verify the IP address is correct");
            $this->warn("  • Check network routing between your server and device");
            $this->warn("  • If device is on different network, ensure VPN/tunnel is active");
            $this->warn("  • Check network cables and switches");
        }
        $this->newLine();

        // Test 2: UDP Port Accessibility
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Test 2: UDP Port Accessibility");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $portBar = $this->output->createProgressBar(1);
        $portBar->start();

        $portResult = $this->testUdpPort($device->ip_address, $device->port);
        $portBar->finish();
        $this->newLine();

        if ($portResult['success']) {
            $this->info("✓ PASS: {$portResult['message']}");
        } else {
            $this->error("✗ FAIL: {$portResult['message']}");
            $this->warn("Troubleshooting Tips:");
            if ($this->isWindows()) {
                $this->warn("  • Check Windows Firewall settings");
                $this->warn("    Run: netsh advfirewall firewall show rule name=all | findstr 4370");
                $this->warn("  • Add firewall rule:");
                $this->warn("    netsh advfirewall firewall add rule name=\"ZKTeco UDP\" protocol=UDP dir=out localport=4370 action=allow");
            } else {
                $this->warn("  • Check EC2 Security Group outbound UDP {$device->port}");
                $this->warn("  • Check Network ACL rules for UDP request/response traffic");
                $this->warn("  • Check server firewall (ufw/iptables/nftables)");
                $this->warn("  • Verify upstream router/NAT allows UDP {$device->port} return packets");
            }
            $this->warn("  • Check network firewall/router settings");
            $this->warn("  • Verify device firewall settings (if any)");
            $this->warn("  • Contact network administrator for cross-network UDP access");
        }
        $this->newLine();

        // Test 3: DNS Resolution
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Test 3: Network Route Check");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $this->testRoute($device->ip_address);
        $this->newLine();

        // Test 4: Device Connection
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Test 4: ZKTeco Device Connection");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $connectionBar = $this->output->createProgressBar(1);
        $connectionBar->start();

        $result = $this->zkService->testConnection($device);
        $connectionBar->finish();
        $this->newLine();

        if ($result['success']) {
            $this->info("✓ SUCCESS: Connection established!");
            $this->newLine();

            if (isset($result['device_info']) && $result['device_info']) {
                $this->info("Device Information:");
                $this->table(
                    ['Property', 'Value'],
                    collect($result['device_info'])->map(fn($v, $k) => [$k, $v])
                );
            }
        } else {
            $this->error("✗ FAILED: {$result['message']}");
            $this->newLine();

            // Provide specific recommendations based on previous tests
            $this->warn("╔════════════════════════════════════════════════════════════╗");
            $this->warn("║           RECOMMENDATIONS FOR YOUR SITUATION               ║");
            $this->warn("╚════════════════════════════════════════════════════════════╝");
            $this->newLine();

            if (!$pingResult['success']) {
                $this->error("Primary Issue: Network connectivity problem");
                $this->warn("The device is not reachable from your server.");
                $this->warn("Since the device is on 'the other network', you need:");
                $this->warn("  1. VPN or network tunnel between networks");
                $this->warn("  2. Proper routing configuration");
                $this->warn("  3. Contact network admin to establish connectivity");
            } else if (!$portResult['success']) {
                $this->error("Primary Issue: Firewall blocking UDP port 4370");
                $this->warn("The device is reachable, but UDP traffic is blocked.");
                $this->warn("Actions to take:");
                if ($this->isWindows()) {
                    $this->warn("  1. Add Windows Firewall rule (see Test 2 tips above)");
                    $this->warn("  2. Check corporate/network firewall settings");
                    $this->warn("  3. Ask network admin to allow UDP 4370 between networks");
                } else {
                    $this->warn("  1. Allow outbound UDP 4370 in EC2 Security Group");
                    $this->warn("  2. Allow UDP return path in Network ACL");
                    $this->warn("  3. Allow UDP 4370 on instance firewall (ufw/iptables)");
                    $this->warn("  4. Ask network admin to allow AWS source IP to reach device");
                }
            } else {
                $this->error("Primary Issue: Device not responding");
                $this->warn("Network is OK, but device isn't responding.");
                $this->warn("Check:");
                $this->warn("  • Device is powered on and functioning");
                $this->warn("  • IP address configuration on device is correct");
                $this->warn("  • Device firmware is up to date");
                $this->warn("  • No password protection on device");
            }
        }

        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║                 Diagnostics Complete                       ║");
        $this->info("╚════════════════════════════════════════════════════════════╝");

        return $result['success'] ? 0 : 1;
    }

    private function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    private function buildPingCommand(string $ip): string
    {
        $escapedIp = escapeshellarg($ip);

        if ($this->isWindows()) {
            return "ping -n 1 -w 3000 {$escapedIp}";
        }

        return "ping -c 1 -W 3 {$escapedIp}";
    }

    private function buildRouteCommand(string $ip): ?string
    {
        $escapedIp = escapeshellarg($ip);

        if ($this->isWindows()) {
            return "tracert -h 5 -w 1000 {$escapedIp}";
        }

        $routeCommands = [
            'traceroute -m 5 -w 1 ' . $escapedIp,
            'tracepath ' . $escapedIp,
        ];

        foreach ($routeCommands as $command) {
            $binary = strtok($command, ' ');
            $probeOutput = [];
            $probeStatus = 1;
            exec("command -v {$binary} >/dev/null 2>&1", $probeOutput, $probeStatus);

            if ($probeStatus === 0) {
                return $command;
            }
        }

        return null;
    }

    private function testPing(string $ip): array
    {
        try {
            $output = [];
            $returnVar = 0;

            exec($this->buildPingCommand($ip) . ' 2>&1', $output, $returnVar);

            $success = ($returnVar === 0);
            $details = implode("\n", $output);
            $message = $success
                ? 'Device is reachable on the network'
                : 'Device is not reachable (ping timeout, network unreachable, or ping unavailable)';

            if (!$success && stripos($details, 'not found') !== false) {
                $message = 'Ping command is unavailable on this server.';
            }

            return [
                'success' => $success,
                'message' => $message,
                'details' => $details,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ping test failed: ' . $e->getMessage(),
                'details' => ''
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
                    'details' => 'socket_create() is unavailable on this server.'
                ];
            }

            $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

            if (!$socket) {
                return [
                    'success' => false,
                    'message' => 'Failed to create UDP socket',
                    'details' => socket_strerror(socket_last_error())
                ];
            }

            $timeout = ['sec' => 5, 'usec' => 0];
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout);
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeout);

            $testData = "TEST";
            $sent = @socket_sendto($socket, $testData, strlen($testData), 0, $ip, $port);

            if ($sent === false) {
                $errorCode = socket_last_error($socket);
                $errorMessage = socket_strerror($errorCode);
                socket_close($socket);

                return [
                    'success' => false,
                    'message' => 'Failed to send data to UDP port (likely blocked by firewall)',
                    'details' => $errorMessage,
                ];
            }

            socket_close($socket);

            return [
                'success' => true,
                'message' => "UDP port is accessible (sent {$sent} bytes)",
                'details' => "Sent to {$ip}:{$port}"
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'UDP port test failed: ' . $e->getMessage(),
                'details' => ''
            ];
        }
    }

    private function testRoute(string $ip): void
    {
        try {
            $command = $this->buildRouteCommand($ip);
            if (!$command) {
                $this->warn('Route test skipped: traceroute tools are not installed on this server.');
                return;
            }

            $output = [];
            exec($command . ' 2>&1', $output);

            $this->info("Route tracing to {$ip} (first 5 hops):");
            foreach (array_slice($output, 0, 10) as $line) {
                $this->line("  " . $line);
            }

            if (count($output) > 10) {
                $this->line("  ...");
            }
        } catch (\Exception $e) {
            $this->warn("Route test failed: " . $e->getMessage());
        }
    }
}
