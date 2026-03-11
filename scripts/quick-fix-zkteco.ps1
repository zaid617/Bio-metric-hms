# Quick Fix Script for ZKTeco Cross-Network Connection
# This script attempts common fixes automatically

param(
    [Parameter(Mandatory=$false)]
    [string]$DeviceIP = "182.184.76.121",

    [Parameter(Mandatory=$false)]
    [int]$Port = 4370,

    [Parameter(Mandatory=$false)]
    [string]$GatewayIP = ""
)

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "⚠ WARNING: Not running as Administrator" -ForegroundColor Red
    Write-Host "Some fixes require Administrator privileges." -ForegroundColor Yellow
    Write-Host "Please run PowerShell as Administrator for full functionality." -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to continue anyway, or Ctrl+C to exit"
}

Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "    ZKTeco Quick Fix Script" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Test current connectivity
Write-Host "Testing current connectivity to $DeviceIP..." -ForegroundColor Cyan
$pingTest = Test-Connection -ComputerName $DeviceIP -Count 2 -Quiet

if ($pingTest) {
    Write-Host "✓ Device is currently reachable" -ForegroundColor Green
    Write-Host ""

    # Device is reachable, likely a firewall issue
    Write-Host "Device is reachable. Applying firewall fixes..." -ForegroundColor Yellow
    Write-Host ""

    if ($isAdmin) {
        # Fix 1: Add Windows Firewall Rules
        Write-Host "─────────────────────────────────────────────────────────────" -ForegroundColor Gray
        Write-Host "Fix 1: Adding Windows Firewall Rules" -ForegroundColor White
        Write-Host "─────────────────────────────────────────────────────────────" -ForegroundColor Gray

        try {
            # Remove existing rules if any
            Remove-NetFirewallRule -DisplayName "ZKTeco Device UDP Out" -ErrorAction SilentlyContinue
            Remove-NetFirewallRule -DisplayName "ZKTeco Device UDP In" -ErrorAction SilentlyContinue

            # Add outbound rule
            New-NetFirewallRule -DisplayName "ZKTeco Device UDP Out" `
                -Direction Outbound `
                -Protocol UDP `
                -RemoteAddress $DeviceIP `
                -RemotePort $Port `
                -Action Allow `
                -ErrorAction Stop | Out-Null

            Write-Host "✓ Added outbound firewall rule" -ForegroundColor Green

            # Add inbound rule
            New-NetFirewallRule -DisplayName "ZKTeco Device UDP In" `
                -Direction Inbound `
                -Protocol UDP `
                -LocalPort $Port `
                -Action Allow `
                -ErrorAction Stop | Out-Null

            Write-Host "✓ Added inbound firewall rule" -ForegroundColor Green

        } catch {
            Write-Host "✗ Failed to add firewall rules: $_" -ForegroundColor Red
        }
        Write-Host ""

    } else {
        Write-Host "⚠ Skipping firewall configuration (requires Administrator)" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "To add firewall rules manually, run as Administrator:" -ForegroundColor Yellow
        Write-Host "New-NetFirewallRule -DisplayName 'ZKTeco Device UDP Out' -Direction Outbound -Protocol UDP -RemoteAddress $DeviceIP -RemotePort $Port -Action Allow" -ForegroundColor Cyan
        Write-Host "New-NetFirewallRule -DisplayName 'ZKTeco Device UDP In' -Direction Inbound -Protocol UDP -LocalPort $Port -Action Allow" -ForegroundColor Cyan
        Write-Host ""
    }

} else {
    Write-Host "✗ Device is NOT reachable" -ForegroundColor Red
    Write-Host ""

    # Device not reachable, likely routing/VPN issue
    Write-Host "Device is not reachable. Checking network configuration..." -ForegroundColor Yellow
    Write-Host ""

    # Check for VPN connections
    Write-Host "─────────────────────────────────────────────────────────────" -ForegroundColor Gray
    Write-Host "Checking VPN Connections" -ForegroundColor White
    Write-Host "─────────────────────────────────────────────────────────────" -ForegroundColor Gray

    $vpnConnections = Get-VpnConnection -ErrorAction SilentlyContinue
    if ($vpnConnections) {
        Write-Host "Found VPN connections:" -ForegroundColor Cyan
        foreach ($vpn in $vpnConnections) {
            $status = if ($vpn.ConnectionStatus -eq "Connected") { "✓" } else { "✗" }
            $color = if ($vpn.ConnectionStatus -eq "Connected") { "Green" } else { "Red" }
            Write-Host "$status $($vpn.Name): $($vpn.ConnectionStatus)" -ForegroundColor $color
        }
        Write-Host ""

        $disconnectedVpn = $vpnConnections | Where-Object { $_.ConnectionStatus -ne "Connected" }
        if ($disconnectedVpn) {
            Write-Host "Would you like to connect a VPN? (This might solve the issue)" -ForegroundColor Yellow
            $connect = Read-Host "Enter VPN name to connect, or press Enter to skip"

            if ($connect) {
                try {
                    rasdial $connect
                    Write-Host "✓ Attempted to connect $connect" -ForegroundColor Green
                    Start-Sleep -Seconds 3

                    # Test again
                    $pingTest2 = Test-Connection -ComputerName $DeviceIP -Count 2 -Quiet
                    if ($pingTest2) {
                        Write-Host "✓ Device is now reachable!" -ForegroundColor Green
                    }
                } catch {
                    Write-Host "✗ Failed to connect VPN: $_" -ForegroundColor Red
                }
            }
        }
    } else {
        Write-Host "⚠ No VPN connections found" -ForegroundColor Yellow
    }
    Write-Host ""

    # Check and offer to add static route
    Write-Host "─────────────────────────────────────────────────────────────" -ForegroundColor Gray
    Write-Host "Checking Routing Table" -ForegroundColor White
    Write-Host "─────────────────────────────────────────────────────────────" -ForegroundColor Gray

    $existingRoute = Get-NetRoute -DestinationPrefix "182.184.*" -ErrorAction SilentlyContinue

    if ($existingRoute) {
        Write-Host "Found existing routes to 182.184.x.x network:" -ForegroundColor Cyan
        $existingRoute | Format-Table DestinationPrefix, NextHop, InterfaceAlias -AutoSize
    } else {
        Write-Host "⚠ No route found to 182.184.x.x network" -ForegroundColor Red
        Write-Host ""

        if ($isAdmin) {
            if (-not $GatewayIP) {
                Write-Host "To add a static route, we need the gateway IP address." -ForegroundColor Yellow
                Write-Host "Your current default gateway(s):" -ForegroundColor Cyan

                $defaultGateways = Get-NetRoute -DestinationPrefix "0.0.0.0/0" | Select-Object NextHop, InterfaceAlias
                $defaultGateways | Format-Table -AutoSize

                $GatewayIP = Read-Host "Enter gateway IP for device network (or press Enter to skip)"
            }

            if ($GatewayIP) {
                try {
                    Write-Host "Adding static route: 182.184.0.0/16 via $GatewayIP" -ForegroundColor Cyan
                    New-NetRoute -DestinationPrefix "182.184.0.0/16" -NextHop $GatewayIP -PolicyStore PersistentStore -ErrorAction Stop | Out-Null
                    Write-Host "✓ Static route added" -ForegroundColor Green

                    # Test connectivity again
                    Start-Sleep -Seconds 2
                    $pingTest3 = Test-Connection -ComputerName $DeviceIP -Count 2 -Quiet
                    if ($pingTest3) {
                        Write-Host "✓ Device is now reachable!" -ForegroundColor Green
                    } else {
                        Write-Host "⚠ Route added but device still not reachable" -ForegroundColor Yellow
                        Write-Host "  The gateway might be incorrect, or there are other network issues" -ForegroundColor Yellow
                    }
                } catch {
                    Write-Host "✗ Failed to add route: $_" -ForegroundColor Red
                }
            }
        } else {
            Write-Host "⚠ Cannot add route without Administrator privileges" -ForegroundColor Yellow
            Write-Host ""
            Write-Host "To add route manually, run as Administrator:" -ForegroundColor Yellow
            Write-Host "New-NetRoute -DestinationPrefix '182.184.0.0/16' -NextHop <gateway_ip> -PolicyStore PersistentStore" -ForegroundColor Cyan
        }
    }
    Write-Host ""
}

# Final connectivity test
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "    Final Connectivity Test" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

Write-Host "Testing connection to $DeviceIP..." -ForegroundColor Cyan
$finalPing = Test-Connection -ComputerName $DeviceIP -Count 3 -ErrorAction SilentlyContinue

if ($finalPing) {
    Write-Host "✓ SUCCESS: Device is reachable!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Average Response Time: $([math]::Round(($finalPing | Measure-Object -Property ResponseTime -Average).Average, 2)) ms" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor Yellow
    Write-Host "1. Test the Laravel connection:" -ForegroundColor White
    Write-Host "   cd c:\laragon\www\bio-metric" -ForegroundColor Cyan
    Write-Host "   php artisan zkteco:diagnose" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "2. If that works, try syncing:" -ForegroundColor White
    Write-Host "   php artisan zkteco:sync" -ForegroundColor Cyan

} else {
    Write-Host "✗ FAILED: Device is still not reachable" -ForegroundColor Red
    Write-Host ""
    Write-Host "Additional troubleshooting needed:" -ForegroundColor Yellow
    Write-Host "1. Verify the device IP address is correct: $DeviceIP" -ForegroundColor White
    Write-Host "2. Check if device is powered on" -ForegroundColor White
    Write-Host "3. Verify device is on network 182.184.76.x" -ForegroundColor White
    Write-Host "4. Contact network administrator for help with:" -ForegroundColor White
    Write-Host "   • Network routing between your server and device" -ForegroundColor Cyan
    Write-Host "   • VPN access to device network" -ForegroundColor Cyan
    Write-Host "   • Firewall configurations" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Run the diagnostic script for more details:" -ForegroundColor White
    Write-Host "   .\scripts\test-zkteco-connection.ps1 -DeviceIP $DeviceIP" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
