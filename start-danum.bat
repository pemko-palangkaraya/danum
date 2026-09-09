@echo off
setlocal

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\start-danum.ps1"

if errorlevel 1 (
    echo.
    echo DANUM gagal dijalankan. Periksa pesan di atas.
)

pause
