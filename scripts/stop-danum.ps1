$ErrorActionPreference = 'Continue'

Write-Host ''
Write-Host '========================================' -ForegroundColor Cyan
Write-Host '          DANUM SERVICE STOPPER' -ForegroundColor Cyan
Write-Host '========================================' -ForegroundColor Cyan
Write-Host ''

function Stop-ProcessTree {
    param(
        [int]$TargetProcessId,
        [string]$Label
    )

    if (-not $TargetProcessId) {
        return
    }

    Write-Host "[STOP] $Label (PID $TargetProcessId)..." -ForegroundColor Yellow
    & taskkill.exe /PID $TargetProcessId /T /F | Out-Host

    if ($LASTEXITCODE -eq 0) {
        Write-Host "[OK] $Label berhasil dihentikan." -ForegroundColor Green
    } else {
        Write-Host "[WARN] $Label gagal dihentikan (exit code $LASTEXITCODE)." -ForegroundColor Red
    }
}

function Stop-ByCommandLine {
    param(
        [string]$Pattern,
        [string]$Label
    )

    $processes = Get-CimInstance Win32_Process |
        Where-Object {
            $_.CommandLine -and $_.CommandLine -match $Pattern
        }

    if (-not $processes) {
        Write-Host "[OK] $Label tidak sedang berjalan." -ForegroundColor Green
        return
    }

    foreach ($process in $processes) {
        Stop-ProcessTree -TargetProcessId $process.ProcessId -Label $Label
    }
}

# Laravel workers: target berdasarkan command line agar PHP lain tidak ikut mati.
Stop-ByCommandLine 'artisan\s+schedule:work' 'Laravel Scheduler'
Stop-ByCommandLine 'artisan\s+queue:work\s+database\s+--queue=default' 'Laravel Queue'

# PHP-CGI DANUM: target proses yang benar-benar memiliki port 9000.
$phpCgiConnections = Get-NetTCPConnection -LocalPort 9000 -State Listen -ErrorAction SilentlyContinue
if ($phpCgiConnections) {
    $phpCgiPids = $phpCgiConnections | Select-Object -ExpandProperty OwningProcess -Unique

    foreach ($targetPid in $phpCgiPids) {
        $process = Get-Process -Id $targetPid -ErrorAction SilentlyContinue
        if ($process -and $process.ProcessName -ieq 'php-cgi') {
            Stop-ProcessTree -TargetProcessId $targetPid -Label 'PHP-CGI :9000'
        } elseif ($process) {
            Write-Host "[WARN] Port 9000 digunakan PID $targetPid ($($process.ProcessName)); tidak dihentikan karena bukan php-cgi." -ForegroundColor Yellow
        } else {
            Write-Host "[OK] PID $targetPid sudah tidak berjalan." -ForegroundColor Green
        }
    }
} else {
    Write-Host '[OK] PHP-CGI :9000 tidak sedang berjalan.' -ForegroundColor Green
}

# Nginx DANUM: hentikan seluruh process tree nginx.
$nginxProcesses = Get-Process -Name nginx -ErrorAction SilentlyContinue
if ($nginxProcesses) {
    foreach ($nginx in $nginxProcesses) {
        Stop-ProcessTree -TargetProcessId $nginx.Id -Label 'Nginx'
    }
} else {
    Write-Host '[OK] Nginx tidak sedang berjalan.' -ForegroundColor Green
}

Write-Host ''
Write-Host 'DANUM service stop selesai.' -ForegroundColor Cyan
Write-Host ''
