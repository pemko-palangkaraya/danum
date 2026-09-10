$ErrorActionPreference = 'Stop'

$Danum = 'C:\Users\yudhistira\Herd\danum'
$BackupScript = Join-Path $Danum 'scripts\backup-danum-db.ps1'
$TaskName = 'DANUM - Daily Database Backup'
$PowerShell = (Get-Command powershell.exe -ErrorAction Stop).Source

if (-not (Test-Path $BackupScript)) {
    throw "Script backup tidak ditemukan: $BackupScript"
}

$action = New-ScheduledTaskAction `
    -Execute $PowerShell `
    -Argument "-NoProfile -NonInteractive -ExecutionPolicy Bypass -File `"$BackupScript`""

$trigger = New-ScheduledTaskTrigger -Daily -At '23:00'

$principal = New-ScheduledTaskPrincipal `
    -UserId 'SYSTEM' `
    -LogonType ServiceAccount `
    -RunLevel Highest

$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Hours 2) `
    -MultipleInstances IgnoreNew

Register-ScheduledTask `
    -TaskName $TaskName `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings `
    -Description 'Backup database PostgreSQL DANUM setiap hari pukul 23:00.' `
    -Force | Out-Null

Write-Host ''
Write-Host '========================================' -ForegroundColor Cyan
Write-Host '      DANUM DATABASE BACKUP TASK' -ForegroundColor Cyan
Write-Host '========================================' -ForegroundColor Cyan
Write-Host "[OK] Task       : $TaskName" -ForegroundColor Green
Write-Host '[OK] Jadwal     : Setiap hari pukul 23:00' -ForegroundColor Green
Write-Host '[OK] Run as     : SYSTEM' -ForegroundColor Green
Write-Host "[OK] Script     : $BackupScript" -ForegroundColor Green
Write-Host ''
Write-Host 'Backup pertama dapat diuji dari Task Scheduler dengan opsi Run.' -ForegroundColor Yellow
Write-Host ''
