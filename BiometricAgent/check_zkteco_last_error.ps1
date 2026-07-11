try {
    $sdk = New-Object -ComObject 'zkemkeeper.ZKEM.1'
    Write-Host "COM object created"
    $res = $sdk.Connect_Net('192.168.1.212', 4370)
    Write-Host "Connect_Net = $res"
    if (-not $res) {
        try {
            $err = 0
            $sdk.GetLastError([ref]$err)
            Write-Host "GetLastError = $err"
        } catch {
            Write-Host "GetLastError call failed: $_"
        }
    }
    if ($res) {
        try { $sdk.Disconnect() | Out-Null } catch {}
    }
} catch {
    Write-Host "Exception: $_"
}
