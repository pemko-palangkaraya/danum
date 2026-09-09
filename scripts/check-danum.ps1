$ErrorActionPreference = 'SilentlyContinue'

Write-Host ''
Write-Host '========================================' -ForegroundColor Cyan
Write-Host '           DANUM SERVICE CHECKER' -ForegroundColor Cyan
Write-Host '========================================' -ForegroundColor Cyan
Write-Host ''

$allOk = $true

function Show-Status {
    param(
        [string]$Label,
        [bool]$Running,
        [string]$Detail
    )

    if ($Running) {
        Write-Host "[ OK ] $Label" -ForegroundColor Green
        if ($Detail) {
            Write-Host "       $Detail" -ForegroundColor DarkGray
        }
    } else {
        Write-Host "[FAIL] $Label" -ForegroundColor Red
        if ($Detail) {
            Write-Host "       $Detail" -ForegroundColor Yellow
        }
        $script:allOk = $false
    }
}

# 1. Nginx
$nginx = Get-Process -Name nginx -ErrorAction SilentlyContinue
$port80 = Get-NetTCPConnection -LocalPort 80 -State Listen -ErrorAction SilentlyContinue
Show-Status `
    -Label 'Nginx' `
    -Running ([bool]$nginx -and [bool]$port80) `
    -Detail $(if ($nginx -and $port80) { "PID: $($nginx.Id -join ', ') | Port: 80 LISTEN" } else { 'Nginx atau port 80 tidak aktif.' })

# 2. PHP-CGI :9000
$phpCgi = Get-Process -Name php-cgi -ErrorAction SilentlyContinue
$port9000 = Get-NetTCPConnection -LocalPort 9000 -State Listen -ErrorAction SilentlyContinue
$phpCgiOnPort = $false
if ($port9000) {
    $phpCgiPids = $port9000 | Select-Object -ExpandProperty OwningProcess -Unique
    foreach ($targetPid in $phpCgiPids) {
        $process = Get-Process -Id $targetPid -ErrorAction SilentlyContinue
        if ($process -and $process.ProcessName -ieq 'php-cgi') {
            $phpCgiOnPort = $true
            break
        }
    }
}
Show-Status `
    -Label 'PHP-CGI :9000' `
    -Running $phpCgiOnPort `
    -Detail $(if ($phpCgiOnPort) { "PID: $($phpCgiPids -join ', ') | Port: 9000 LISTEN" } else { 'PHP-CGI tidak ditemukan pada port 9000.' })

# 3. Laravel Scheduler
$scheduler = Get-CimInstance Win32_Process | Where-Object {
    $_.CommandLine -and $_.CommandLine -match 'artisan\s+schedule:work'
}
Show-Status `
    -Label 'Laravel Scheduler' `
    -Running ([bool]$scheduler) `
    -Detail $(if ($scheduler) { "PID: $($scheduler.ProcessId -join ', ') | schedule:work aktif" } else { 'artisan schedule:work tidak berjalan.' })

# 4. Laravel Queue Worker
$queue = Get-CimInstance Win32_Process | Where-Object {
    $_.CommandLine -and $_.CommandLine -match 'artisan\s+queue:work\s+database\s+--queue=default'
}
Show-Status `
    -Label 'Laravel Queue Worker' `
    -Running ([bool]$queue) `
    -Detail $(if ($queue) { "PID: $($queue.ProcessId -join ', ') | queue:work aktif" } else { 'artisan queue:work database tidak berjalan.' })

Write-Host ''
Write-Host '----------------------------------------'
if ($allOk) {
    Write-Host 'STATUS: DANUM SIAP DIGUNAKAN' -ForegroundColor Green
} else {
    Write-Host 'STATUS: DANUM BELUM SIAP' -ForegroundColor Red
}
Write-Host '----------------------------------------'
Write-Host ''

if (-not $allOk) {
    exit 1
}

exit 0
