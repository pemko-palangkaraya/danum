$ErrorActionPreference = 'Stop'

$Danum = 'C:\Users\yudhistira\Herd\danum'
$BackupDirectory = 'C:\Users\yudhistira\Herd\danum-backups'
$PgDump = 'C:\Program Files\PostgreSQL\18\bin\pg_dump.exe'
$EnvFile = Join-Path $Danum '.env'

function Get-EnvValue {
    param([string]$Name)

    $line = Get-Content $EnvFile | Where-Object {
        $_ -match "^\s*$([regex]::Escape($Name))\s*="
    } | Select-Object -First 1

    if (-not $line) {
        return $null
    }

    $value = ($line -replace "^\s*$([regex]::Escape($Name))\s*=\s*", '').Trim()

    if ($value.Length -ge 2 -and (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'")))) {
        $value = $value.Substring(1, $value.Length - 2)
    }

    return $value
}

if (-not (Test-Path $PgDump)) {
    throw "pg_dump tidak ditemukan: $PgDump"
}

if (-not (Test-Path $EnvFile)) {
    throw ".env tidak ditemukan: $EnvFile"
}

$dbConnection = Get-EnvValue 'DB_CONNECTION'
if ($dbConnection -and $dbConnection.ToLowerInvariant() -ne 'pgsql') {
    throw "Backup deployment ini hanya mendukung PostgreSQL. DB_CONNECTION saat ini: $dbConnection"
}

$dbHost = Get-EnvValue 'DB_HOST'
$dbPort = Get-EnvValue 'DB_PORT'
$dbDatabase = Get-EnvValue 'DB_DATABASE'
$dbUsername = Get-EnvValue 'DB_USERNAME'
$dbPassword = Get-EnvValue 'DB_PASSWORD'

if ([string]::IsNullOrWhiteSpace($dbHost) -or
    [string]::IsNullOrWhiteSpace($dbPort) -or
    [string]::IsNullOrWhiteSpace($dbDatabase) -or
    [string]::IsNullOrWhiteSpace($dbUsername)) {
    throw 'Konfigurasi DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME di .env tidak lengkap.'
}

New-Item -ItemType Directory -Force -Path $BackupDirectory | Out-Null

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupFile = Join-Path $BackupDirectory "danum-$timestamp.dump"

Write-Host ''
Write-Host '========================================' -ForegroundColor Cyan
Write-Host '          DANUM DATABASE BACKUP' -ForegroundColor Cyan
Write-Host '========================================' -ForegroundColor Cyan
Write-Host "[BACKUP] Database : $dbDatabase" -ForegroundColor Yellow
Write-Host "[BACKUP] Host     : $dbHost`:$dbPort" -ForegroundColor Yellow
Write-Host "[BACKUP] File     : $backupFile" -ForegroundColor Yellow
Write-Host ''

$env:PGHOST = $dbHost
$env:PGPORT = $dbPort
$env:PGDATABASE = $dbDatabase
$env:PGUSER = $dbUsername
$env:PGPASSWORD = $dbPassword

try {
    & $PgDump --format=custom --file="$backupFile" --no-password

    if ($LASTEXITCODE -ne 0) {
        throw "pg_dump gagal dengan exit code $LASTEXITCODE."
    }

    if (-not (Test-Path $backupFile)) {
        throw 'pg_dump selesai tetapi file backup tidak ditemukan.'
    }

    $backupSize = (Get-Item $backupFile).Length
    if ($backupSize -le 0) {
        throw 'File backup kosong.'
    }

    Write-Host "[OK] Backup berhasil: $backupFile" -ForegroundColor Green
    Write-Host "[OK] Ukuran backup : $([math]::Round($backupSize / 1MB, 2)) MB" -ForegroundColor Green

    # Simpan maksimal 10 backup terbaru agar folder backup tidak tumbuh tanpa batas.
    $backups = Get-ChildItem $BackupDirectory -Filter 'danum-*.dump' -File |
        Sort-Object LastWriteTime -Descending

    if ($backups.Count -gt 10) {
        $backups | Select-Object -Skip 10 | Remove-Item -Force
        Write-Host '[CLEANUP] Backup lama dihapus; 10 backup terbaru dipertahankan.' -ForegroundColor Yellow
    }
}
finally {
    Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
    Remove-Item Env:PGHOST -ErrorAction SilentlyContinue
    Remove-Item Env:PGPORT -ErrorAction SilentlyContinue
    Remove-Item Env:PGDATABASE -ErrorAction SilentlyContinue
    Remove-Item Env:PGUSER -ErrorAction SilentlyContinue
}

Write-Host ''
