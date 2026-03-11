# ZKTeco Device Network Connectivity Test Script
# Run this script to diagnose connection issues to your ZKTeco device

param(
    [Parameter(Mandatory=$true)]
    [string]$DeviceIP = "182.184.76.121",

    [Parameter(Mandatory=$false)]
    [int]$Port = 4370
)

Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "    ZKTeco Device Network Diagnostics" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""
Write-Host "Device IP: $DeviceIP" -ForegroundColor Yellow
Write-Host "Port: $Port" -ForegroundColor Yellow
Write-Host ""

# Test 1: Ping Test
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray
Write-Host "Test 1: Ping Test (Network Reachability)" -ForegroundColor White
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

$pingResult = Test-Connection -ComputerName $DeviceIP -Count 2 -Quiet
if ($pingResult) {
    Write-Host "✓ PASS: Device is reachable" -ForegroundColor Green
} else {
    Write-Host "✗ FAIL: Device is NOT reachable" -ForegroundColor Red
    Write-Host "  Possible causes:" -ForegroundColor Yellow
    Write-Host "    • Device is powered off" -ForegroundColor Yellow
    Write-Host "    • Wrong IP address" -ForegroundColor Yellow
    Write-Host "    • Network routing issue (VPN not connected?)" -ForegroundColor Yellow
    Write-Host "    • Firewall blocking ICMP (ping)" -ForegroundColor Yellow
}
Write-Host ""

# Test 2: Detailed Ping
Write-Host "Detailed Ping Results:" -ForegroundColor Cyan
$pingDetails = Test-Connection -ComputerName $DeviceIP -Count 4
$pingDetails | Format-Table -Property Address, IPv4Address, ResponseTime, Status -AutoSize
Write-Host ""

# Test 3: Route Trace
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray
Write-Host "Test 2: Route Trace (Path to Device)" -ForegroundColor White
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

try {
    Write-Host "Tracing route to $DeviceIP (max 10 hops)..." -ForegroundColor Cyan
    $traceResult = Test-NetConnection -ComputerName $DeviceIP -TraceRoute -Hops 10 -WarningAction SilentlyContinue

    if ($traceResult.TraceRoute) {
        Write-Host "Route:" -ForegroundColor Cyan
        $hopNumber = 1
        foreach ($hop in $traceResult.TraceRoute) {
            Write-Host "  Hop $hopNumber : $hop" -ForegroundColor Gray
            $hopNumber++
        }
    }
} catch {
    Write-Host "Could not trace route: $_" -ForegroundColor Yellow
}
Write-Host ""

# Test 4: Port Connectivity (TCP test - note: device uses UDP but this gives us info)
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray
Write-Host "Test 3: TCP Port Test (Note: Device uses UDP, this is informational)" -ForegroundColor White
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

$tcpTest = Test-NetConnection -ComputerName $DeviceIP -Port $Port -WarningAction SilentlyContinue
if ($tcpTest.TcpTestSucceeded) {
    Write-Host "✓ TCP Port $Port is open" -ForegroundColor Green
} else {
    Write-Host "✗ TCP Port $Port is not responding" -ForegroundColor Yellow
    Write-Host "  Note: This is expected as ZKTeco uses UDP, not TCP" -ForegroundColor Yellow
}
Write-Host ""

# Test 5: Firewall Rules Check
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray
Write-Host "Test 4: Windows Firewall Rules" -ForegroundColor White
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

$firewallRules = Get-NetFirewallRule | Where-Object {
    $_.DisplayName -like "*ZK*" -or
    $_.DisplayName -like "*4370*" -or
    ($_.Enabled -eq $true -and $_.Direction -eq "Outbound" -and $_.Action -eq "Block")
} | Select-Object DisplayName, Direction, Action, Enabled

if ($firewallRules) {
    Write-Host "Found potentially relevant firewall rules:" -ForegroundColor Cyan
    $firewallRules | Format-Table -AutoSize
} else {
    Write-Host "⚠ No specific ZKTeco firewall rules found" -ForegroundColor Yellow
    Write-Host "  You may need to add a rule to allow UDP port $Port" -ForegroundColor Yellow
}
Write-Host ""

# Test 6: Network Adapter Info
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray
Write-Host "Test 5: Network Configuration" -ForegroundColor White
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

$activeAdapters = Get-NetIPAddress -AddressFamily IPv4 | Where-Object {
    $_.IPAddress -notlike "127.*" -and
    $_.IPAddress -notlike "169.254.*"
}

Write-Host "Active Network Adapters:" -ForegroundColor Cyan
foreach ($adapter in $activeAdapters) {
    $adapterName = (Get-NetAdapter -InterfaceIndex $adapter.InterfaceIndex).Name
    Write-Host "  Interface: $adapterName" -ForegroundColor Gray
    Write-Host "    IP: $($adapter.IPAddress)" -ForegroundColor Gray
    Write-Host "    Subnet: $($adapter.PrefixLength)" -ForegroundColor Gray
}
Write-Host ""

# Test 7: Routing Table
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray
Write-Host "Test 6: Routing Table (Routes to 182.184.x.x)" -ForegroundColor White
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

$routes = Get-NetRoute -AddressFamily IPv4 | Where-Object {
    $_.DestinationPrefix -like "182.184.*" -or
    $_.DestinationPrefix -eq "0.0.0.0/0"
} | Select-Object DestinationPrefix, NextHop, InterfaceAlias, RouteMetric

if ($routes) {
    Write-Host "Relevant Routes:" -ForegroundColor Cyan
    $routes | Format-Table -AutoSize
} else {
    Write-Host "⚠ No specific route found to 182.184.x.x network" -ForegroundColor Red
    Write-Host "  This is likely the problem!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Solutions:" -ForegroundColor Yellow
    Write-Host "  1. Connect VPN to device network" -ForegroundColor Yellow
    Write-Host "  2. Add static route:" -ForegroundColor Yellow
    Write-Host "     route add 182.184.0.0 mask 255.255.0.0 <gateway_ip> -p" -ForegroundColor Cyan
}
Write-Host ""

# Test 8: UDP Socket Test
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray
Write-Host "Test 7: UDP Socket Test (Actual Protocol Used)" -ForegroundColor White
Write-Host "───────────────────────────────────────────────────────────────" -ForegroundColor Gray

try {
    $udpClient = New-Object System.Net.Sockets.UdpClient
    $udpClient.Client.ReceiveTimeout = 5000
    $udpClient.Connect($DeviceIP, $Port)

    # Send test packet
    $testData = [System.Text.Encoding]::ASCII.GetBytes("TEST")
    $bytesSent = $udpClient.Send($testData, $testData.Length)

    if ($bytesSent -gt 0) {
        Write-Host "✓ PASS: UDP socket created and test packet sent ($bytesSent bytes)" -ForegroundColor Green
        Write-Host "  This indicates UDP traffic can reach the device" -ForegroundColor Green
    }

    $udpClient.Close()
} catch {
    Write-Host "✗ FAIL: UDP socket test failed" -ForegroundColor Red
    Write-Host "  Error: $_" -ForegroundColor Red
    Write-Host "  Possible causes:" -ForegroundColor Yellow
    Write-Host "    • Firewall blocking UDP traffic" -ForegroundColor Yellow
    Write-Host "    • Network not reachable" -ForegroundColor Yellow
}
Write-Host ""

# Summary and Recommendations
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "    Summary and Recommendations" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

if ($pingResult) {
    Write-Host "✓ Network: Device is reachable" -ForegroundColor Green

    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor Yellow
    Write-Host "1. Check Windows Firewall:" -ForegroundColor White
    Write-Host "   netsh advfirewall firewall add rule name=`"ZKTeco UDP`" protocol=UDP dir=out remoteip=$DeviceIP remoteport=$Port action=allow" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "2. Run Laravel diagnostic:" -ForegroundColor White
    Write-Host "   php artisan zkteco:diagnose" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "3. Check device configuration directly (access device admin panel)" -ForegroundColor White

} else {
    Write-Host "✗ Network: Device is NOT reachable" -ForegroundColor Red
    Write-Host ""
    Write-Host "Critical: Fix network connectivity first!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Action Items:" -ForegroundColor Yellow
    Write-Host "1. Verify device IP is correct: $DeviceIP" -ForegroundColor White
    Write-Host "2. Check if device is powered on" -ForegroundColor White
    Write-Host "3. If device is on different network:" -ForegroundColor White
    Write-Host "   • Connect VPN to device's network" -ForegroundColor Cyan
    Write-Host "   • OR add static route:" -ForegroundColor Cyan
    Write-Host "     route add 182.184.0.0 mask 255.255.0.0 <gateway_ip> -p" -ForegroundColor Cyan
    Write-Host "4. Contact network administrator for assistance" -ForegroundColor White
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""
Write-Host "For detailed troubleshooting guide, see:" -ForegroundColor Gray
Write-Host "docs\ZKTECO_CROSS_NETWORK_TROUBLESHOOTING.md" -ForegroundColor Gray
Write-Host ""
