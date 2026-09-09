@echo off
setlocal

:: Stop service membutuhkan hak Administrator karena beberapa proses
:: dapat berjalan dengan privilege yang berbeda dari terminal pengguna.
net session >nul 2>&1
if not %errorlevel%==0 (
    echo Meminta hak Administrator untuk menghentikan service DANUM...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b 0
)

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\stop-danum.ps1"

if errorlevel 1 (
    echo.
    echo DANUM gagal dihentikan. Periksa pesan di atas.
    pause
    exit /b 1
)

pause
