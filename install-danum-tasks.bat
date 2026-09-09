@echo off
setlocal

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\install-danum-tasks.ps1"

if errorlevel 1 (
    echo.
    echo Gagal mendaftarkan Task Scheduler DANUM.
    echo Jalankan file ini sebagai Administrator.
) else (
    echo.
    echo Task Scheduler DANUM berhasil dipasang.
)

pause
