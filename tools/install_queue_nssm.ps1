Param(
    [string]$NssmPath = "C:\\nssm\\nssm.exe",
    [string]$PhpPath = "C:\\php\\php.exe",
    [string]$ProjectPath = "G:\\Server2\\htdocs\\laravel_institute",
    [string]$ServiceName = "laravel_queue"
)

if (-not (Test-Path $NssmPath)) {
    Write-Error "nssm.exe not found at $NssmPath. Download nssm and place it there or pass -NssmPath."
    exit 1
}

$artisan = "artisan"
$args = "queue:work --sleep=3 --tries=3 --timeout=60 --memory=128"

Write-Host "Installing service '$ServiceName' using nssm at $NssmPath"
& $NssmPath install $ServiceName $PhpPath $artisan $args
& $NssmPath set $ServiceName AppDirectory $ProjectPath
& $NssmPath set $ServiceName AppStdout "$ProjectPath\storage\logs\queue.out.log"
& $NssmPath set $ServiceName AppStderr "$ProjectPath\storage\logs\queue.err.log"
& $NssmPath set $ServiceName Start SERVICE_AUTO_START

Write-Host "Starting service $ServiceName"
& $NssmPath start $ServiceName

Write-Host "Done. Check logs at $ProjectPath\storage\logs\queue.out.log"
