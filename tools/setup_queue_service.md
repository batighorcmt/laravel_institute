# Running Laravel queue worker as a persistent Windows service

This document explains two recommended options to run the Laravel `queue:work` continuously on a Windows live host.

Option A — NSSM (recommended)
- Download NSSM from https://nssm.cc/download and copy `nssm.exe` to `C:\nssm\nssm.exe` or add it to your PATH.
- From an elevated command prompt (Admin) run one of the following variants.

1) Run PHP directly (recommended):

```powershell
nssm install laravel_queue "C:\path\to\php.exe" "artisan" queue:work --sleep=3 --tries=3 --timeout=60 --memory=128
nssm set laravel_queue AppDirectory "G:\Server2\htdocs\laravel_institute"
nssm set laravel_queue AppStdout "G:\Server2\htdocs\laravel_institute\storage\logs\queue.out.log"
nssm set laravel_queue AppStderr "G:\Server2\htdocs\laravel_institute\storage\logs\queue.err.log"
nssm set laravel_queue Start SERVICE_AUTO_START
nssm start laravel_queue
```

2) Use the provided batch wrapper (if you prefer to pass explicit PHP path):

```powershell
nssm install laravel_queue "G:\Server2\htdocs\laravel_institute\tools\run_queue_worker.bat" "C:\path\to\php.exe"
nssm set laravel_queue AppDirectory "G:\Server2\htdocs\laravel_institute"
nssm set laravel_queue AppStdout "G:\Server2\htdocs\laravel_institute\storage\logs\queue.out.log"
nssm set laravel_queue AppStderr "G:\Server2\htdocs\laravel_institute\storage\logs\queue.err.log"
nssm set laravel_queue Start SERVICE_AUTO_START
nssm start laravel_queue
```

Notes:
- Replace `C:\path\to\php.exe` with the PHP binary on your host (for example: `C:\php\php.exe` or `C:\Program Files\PHP\php.exe`).
- `AppDirectory` must point to the Laravel project root so `artisan` resolves.
- Logs are written to `storage/logs/queue.*.log` so you can inspect outputs and errors.

Option B — Scheduled Task (less robust)
- Create a scheduled task that runs at system startup and executes:

```powershell
"C:\path\to\php.exe" "G:\Server2\htdocs\laravel_institute\artisan" queue:work --sleep=3 --tries=3 --timeout=60 --memory=128
```

Ensure the task runs under a service account that has permissions and is set to run whether the user is logged on or not.

Recommended settings and remarks
- Use `--sleep=3 --tries=3 --timeout=60 --memory=128` or tune to match your host capacity.
- Monitor `storage/logs/queue.out.log` and `queue.err.log` and use Windows Event Viewer for additional diagnostics.
- Keep `QUEUE_CONNECTION` set to `database` or `redis` in `.env` in production (do NOT use `sync`).

If you want, I can attempt to create an `nssm` installer script (PowerShell) that installs the service automatically — run it manually on the host as Administrator.
