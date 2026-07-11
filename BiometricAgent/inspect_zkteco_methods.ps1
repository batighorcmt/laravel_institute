try {
    $sdk = New-Object -ComObject 'zkemkeeper.ZKEM.1'
    Write-Host "COM object created"
    $sdk | Get-Member -MemberType Method | Select-Object Name | Sort-Object Name | Format-Table -AutoSize
} catch {
    Write-Host "Exception: $_"
}
