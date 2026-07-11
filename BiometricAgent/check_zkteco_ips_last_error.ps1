$ips = @('192.168.1.201', '192.168.1.212')
foreach ($ip in $ips) {
    Write-Host "--- Testing $ip ---"
    try {
        $sdk = New-Object -ComObject 'zkemkeeper.ZKEM.1'
        $res = $sdk.Connect_Net($ip, 4370)
        Write-Host "Connect_Net = $res"
        if (-not $res) {
            $err = 0
            try {
                $sdk.GetLastError([ref]$err)
            } catch {
                Write-Host "GetLastError call failed: $_"
            }
            Write-Host "GetLastError = $err"
        } else {
            try {
                $connected = $sdk.IsConnected()
                Write-Host "IsConnected = $connected"
            } catch {
                Write-Host "IsConnected call failed: $_"
            }
            try {
                $sdk.Disconnect() | Out-Null
            } catch {}
        }
    } catch {
        Write-Host "Exception: $_"
    }
}
