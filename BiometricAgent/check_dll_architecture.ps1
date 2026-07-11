$path = 'C:\xampp\htdocs\laravel_institute\BiometricAgent\Sdk\zkemkeeper.dll'
if (-Not (Test-Path $path)) { Write-Host "Missing: $path"; exit 1 }
$bytes = [System.IO.File]::ReadAllBytes($path)
$peOffset = [System.BitConverter]::ToUInt32($bytes, 0x3C)
$machine = [System.BitConverter]::ToUInt16($bytes, $peOffset + 4)
Write-Host "Machine = 0x$([Convert]::ToString($machine,16))"
switch ($machine) {
    0x8664 { Write-Host 'x64' }
    0x014c { Write-Host 'x86' }
    0x0200 { Write-Host 'Itanium' }
    default { Write-Host 'Unknown' }
}
