$ErrorActionPreference = 'Stop'

$TaskNames = @(
    'DANUM - Nginx',
    'DANUM - PHP-CGI',
    'DANUM - Scheduler',
    'DANUM - Queue Worker'
)

Write-Host ''
Write-Host '========================================' -ForegroundColor Cyan
Write-Host '        DANUM SERVICE LAUNCHER' -ForegroundColor Cyan
Write-Host '========================================' -ForegroundColor Cyan
Write-Host ''

foreach ($taskName in $TaskNames) {
    $task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

    if (-not $task) {
        throw "Task '$taskName' belum terdaftar. Jalankan scripts\install-danum-tasks.ps1 sebagai Administrator terlebih dahulu."
    }

    Start-ScheduledTask -TaskName $taskName
    Write-Host "[START] $taskName" -ForegroundColor Yellow
}

Write-Host ''
Write-Host 'Semua task DANUM sudah diminta untuk berjalan.' -ForegroundColor Green
Write-Host ''
