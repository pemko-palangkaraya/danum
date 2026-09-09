$ErrorActionPreference = 'Stop'

Write-Host ''
Write-Host '========================================' -ForegroundColor Cyan
Write-Host '          DANUM SERVICE STOPPER' -ForegroundColor Cyan
Write-Host '========================================' -ForegroundColor Cyan
Write-Host ''

function Stop-ByCommandLine {
    param(
        [string]$Pattern,
        [string]$Label
    )

    $processes = Get-CimInstance Win32_Process |
        Where-Object {
            $_.CommandLine -and $_.CommandLine -like "*$Pattern*"
        }

    if (-not $processes) {
        Write-Host "[OK] $Label tidak sedang berjalan." -ForegroundColor Green
        return
    }

    foreach ($process in $processes) {
        Write-Host "[STOP] $Label (PID $($process.ProcessId))..." -ForegroundColor Yellow

        & taskkill.exe /PID $process.ProcessId /T /F | Out-Host

        if ($LASTEXITCODE -ne 0) {
            throw "Gagal menghentikan $Label (PID $($process.ProcessId))."
        }
    }
}

Stop-ByCommandLine 'artisan schedule:work' 'Laravel Scheduler'
Stop-ByCommandLine 'artisan queue:work database --queue=default' 'Laravel Queue'
Stop-ByCommandLine 'php-cgi.exe -b 127.0.0.1:9000' 'PHP-CGI :9000'

$nginx = Get-Process -Name nginx -ErrorAction SilentlyContinue
if ($nginx) {
    foreach ($process in $nginx) {
        Write-Host "[STOP] Nginx (PID $($process.Id))..." -ForegroundColor Yellow
        & taskkill.exe /PID $process.Id /T /F | Out-Host

        if ($LASTEXITCODE -ne 0) {
            throw "Gagal menghentikan Nginx (PID $($process.Id))."
        }
    }
} else {
    Write-Host '[OK] Nginx tidak sedang berjalan.' -ForegroundColor Green
}

Write-Host ''
Write-Host 'DANUM service stop selesai.' -ForegroundColor Cyan
Write-Host ''
