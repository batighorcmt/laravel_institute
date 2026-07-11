$ips = @('192.168.1.201','192.168.1.212')
foreach ($ip in $ips) {
    try {
        Write-Host "Testing $ip:4370"
        $sdk = New-Object -ComObject 'zkemkeeper.ZKEM.1'
        $res = $sdk.Connect_Net($ip, 4370)
        Write-Host "Connect_Net($ip,4370) = $res"
        if ($res) {
            try {
                $connected = $sdk.IsConnected()
                Write-Host "  IsConnected = $connected"
            } catch {
                Write-Host "  IsConnected call failed: $_"
            }
            try { $sdk.Disconnect() | Out-Null } catch {}
        }
    } catch {
        Write-Host "Exception for $ip: $_"
    }
    Write-Host ''
}
