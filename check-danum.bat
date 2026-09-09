@echo off
setlocal

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\check-danum.ps1"

if errorlevel 1 (
    echo.
    echo Ada service DANUM yang belum berjalan atau bermasalah.
)

pause
