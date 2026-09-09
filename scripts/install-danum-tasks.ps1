$ErrorActionPreference = 'Stop'

$Danum = 'C:\Users\yudhistira\Herd\danum'
$Nginx = 'C:\nginx'
$Php = 'C:\Users\yudhistira\.config\herd\bin\php84\php.exe'
$PhpCgi = 'C:\Users\yudhistira\.config\herd\bin\php84\php-cgi.exe'
$TaskPrefix = 'DANUM'

function Register-DanumTask {
    param(
        [string]$Name,
        [string]$Execute,
        [string]$Arguments
    )

    $taskName = "$TaskPrefix - $Name"

    if (Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue) {
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    }

    $action = New-ScheduledTaskAction -Execute $Execute -Argument $Arguments -WorkingDirectory $Danum
    $principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Highest
    $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit ([TimeSpan]::Zero)
    $trigger = New-ScheduledTaskTrigger -AtStartup

    Register-ScheduledTask `
        -TaskName $taskName `
        -Action $action `
        -Principal $principal `
        -Settings $settings `
        -Description "DANUM service: $Name" | Out-Null

    Write-Host "[OK] Task terdaftar: $taskName" -ForegroundColor Green
}

Write-Host ''
Write-Host '========================================' -ForegroundColor Cyan
Write-Host '       DANUM TASK SCHEDULER SETUP' -ForegroundColor Cyan
Write-Host '========================================' -ForegroundColor Cyan
Write-Host ''

Register-DanumTask `
    -Name 'Nginx' `
    -Execute (Join-Path $Nginx 'nginx.exe') `
    -Arguments ''

Register-DanumTask `
    -Name 'PHP-CGI' `
    -Execute $PhpCgi `
    -Arguments '-b 127.0.0.1:9000'

Register-DanumTask `
    -Name 'Scheduler' `
    -Execute $Php `
    -Arguments 'artisan schedule:work'

Register-DanumTask `
    -Name 'Queue Worker' `
    -Execute $Php `
    -Arguments 'artisan queue:work database --queue=default --tries=3 --timeout=900 -vvv'

Write-Host ''
Write-Host 'Semua task DANUM berhasil didaftarkan.' -ForegroundColor Green
Write-Host 'Jalankan start-danum.bat untuk mengaktifkannya.' -ForegroundColor Cyan
Write-Host ''
