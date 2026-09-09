@echo off
setlocal

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\stop-danum.ps1"

if errorlevel 1 (
    echo.
    echo DANUM gagal dihentikan. Periksa pesan di atas.
)

pause
