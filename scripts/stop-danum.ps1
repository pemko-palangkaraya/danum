$ErrorActionPreference = 'Continue'

$TaskNames = @(
    'DANUM - Nginx',
    'DANUM - PHP-CGI',
    'DANUM - Scheduler',
    'DANUM - Queue Worker'
)

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

# Minta Task Scheduler menghentikan task DANUM agar proses tidak langsung hidup kembali.
foreach ($taskName in $TaskNames) {
    $task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

    if ($task) {
        Stop-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
        Write-Host "[STOP] Task $taskName" -ForegroundColor Yellow
    }
}

# Beri waktu singkat agar task menghentikan proses foreground-nya.
Start-Sleep -Seconds 1

# Fallback cleanup: target hanya proses DANUM yang dikenal.
$scheduler = Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object {
    $_.CommandLine -and $_.CommandLine -match 'artisan\s+schedule:work'
}
foreach ($process in $scheduler) {
    Stop-ProcessTree -TargetProcessId $process.ProcessId -Label 'Laravel Scheduler'
}

$queue = Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object {
    $_.CommandLine -and $_.CommandLine -match 'artisan\s+queue:work\s+database\s+--queue=default'
}
foreach ($process in $queue) {
    Stop-ProcessTree -TargetProcessId $process.ProcessId -Label 'Laravel Queue'
}

$phpCgiConnections = Get-NetTCPConnection -LocalPort 9000 -State Listen -ErrorAction SilentlyContinue
if ($phpCgiConnections) {
    $phpCgiPids = $phpCgiConnections | Select-Object -ExpandProperty OwningProcess -Unique

    foreach ($targetPid in $phpCgiPids) {
        $process = Get-Process -Id $targetPid -ErrorAction SilentlyContinue
        if ($process -and $process.ProcessName -ieq 'php-cgi') {
            Stop-ProcessTree -TargetProcessId $targetPid -Label 'PHP-CGI :9000'
        } elseif ($process) {
            Write-Host "[WARN] Port 9000 digunakan PID $targetPid ($($process.ProcessName)); tidak dihentikan karena bukan php-cgi." -ForegroundColor Yellow
        }
    }
}

$nginxProcesses = Get-Process -Name nginx -ErrorAction SilentlyContinue
if ($nginxProcesses) {
    foreach ($nginx in $nginxProcesses) {
        Stop-ProcessTree -TargetProcessId $nginx.Id -Label 'Nginx'
    }
}

Write-Host ''
Write-Host 'DANUM service stop selesai.' -ForegroundColor Cyan
Write-Host ''
