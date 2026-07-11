try {
    $sdk = New-Object -ComObject 'zkemkeeper.ZKEM.1'
    Write-Host "COM object created"
    $res = $sdk.Connect_Net('192.168.1.212', 4370)
    Write-Host "Connect_Net = $res"
    if ($res) {
        try {
            $connected = $sdk.IsConnected()
            Write-Host "IsConnected = $connected"
        } catch {
            Write-Host "IsConnected call failed: $_"
        }
        $sdk.Disconnect() | Out-Null
    }
} catch {
    Write-Host "Exception: $_"
}
