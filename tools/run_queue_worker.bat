@echo off
REM Usage: run_queue_worker.bat C:\path\to\php.exe
IF "%~1"=="" (
  echo Usage: %~nx0 C:\path\to\php.exe
  exit /b 1
)
set "PHP_EXE=%~1"
REM Change directory to repo root (assumes script is in tools\)
pushd %~dp0\..\
%PHP_EXE% artisan queue:work --sleep=3 --tries=3 --timeout=60 --memory=128
set EXITCODE=%ERRORLEVEL%
popd
exit /b %EXITCODE%
