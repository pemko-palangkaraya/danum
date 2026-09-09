$ErrorActionPreference = 'Stop'

$Danum = 'C:\Users\yudhistira\Herd\danum'
$Nginx = 'C:\nginx'
$PhpCgi = 'C:\Users\yudhistira\.config\herd\bin\php84\php-cgi.exe'

function Get-ProcessCommandLines {
    Get-CimInstance Win32_Process -ErrorAction SilentlyContinue |
        Where-Object { $_.CommandLine } |
        Select-Object ProcessId, Name, CommandLine
}

function Test-RunningCommand {
    param([string]$Pattern)

    return [bool](Get-ProcessCommandLines | Where-Object { $_.CommandLine -like "*$Pattern*" })
}

Write-Host ''
Write-Host '========================================' -ForegroundColor Cyan
Write-Host '        DANUM SERVICE LAUNCHER' -ForegroundColor Cyan
Write-Host '========================================' -ForegroundColor Cyan
Write-Host ''

if (Get-Process -Name nginx -ErrorAction SilentlyContinue) {
    Write-Host '[OK] Nginx sudah berjalan.' -ForegroundColor Green
} else {
    Write-Host '[START] Nginx...' -ForegroundColor Yellow
    Start-Process -FilePath (Join-Path $Nginx 'nginx.exe') -WorkingDirectory $Nginx
}

if (Get-NetTCPConnection -LocalAddress 127.0.0.1 -LocalPort 9000 -State Listen -ErrorAction SilentlyContinue) {
    Write-Host '[OK] PHP-CGI :9000 sudah berjalan.' -ForegroundColor Green
} else {
    Write-Host '[START] PHP-CGI :9000...' -ForegroundColor Yellow
    Start-Process -FilePath $PhpCgi -ArgumentList '-b 127.0.0.1:9000' -WorkingDirectory $Danum
}

if (Test-RunningCommand 'artisan schedule:work') {
    Write-Host '[OK] Laravel Scheduler sudah berjalan.' -ForegroundColor Green
} else {
    Write-Host '[START] Laravel Scheduler...' -ForegroundColor Yellow
    Start-Process -FilePath 'php' -ArgumentList 'artisan schedule:work' -WorkingDirectory $Danum
}

if (Test-RunningCommand 'artisan queue:work database --queue=default') {
    Write-Host '[OK] Laravel Queue sudah berjalan.' -ForegroundColor Green
} else {
    Write-Host '[START] Laravel Queue...' -ForegroundColor Yellow
    Start-Process -FilePath 'php' -ArgumentList 'artisan queue:work database --queue=default --tries=3 --timeout=900 -vvv' -WorkingDirectory $Danum
}

Write-Host ''
Write-Host 'DANUM service check selesai.' -ForegroundColor Cyan
Write-Host ''
