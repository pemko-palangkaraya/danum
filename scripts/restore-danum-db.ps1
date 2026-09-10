$ErrorActionPreference = 'Stop'

$Danum = 'C:\Users\yudhistira\Herd\danum'
$BackupDirectory = 'C:\Users\yudhistira\Herd\danum-backups'
$PgDump = 'C:\Program Files\PostgreSQL\18\bin\pg_dump.exe'
$PgRestore = 'C:\Program Files\PostgreSQL\18\bin\pg_restore.exe'
$Psql = 'C:\Program Files\PostgreSQL\18\bin\psql.exe'
$Createdb = 'C:\Program Files\PostgreSQL\18\bin\createdb.exe'
$Dropdb = 'C:\Program Files\PostgreSQL\18\bin\dropdb.exe'
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

foreach ($tool in @($PgDump, $PgRestore, $Psql, $Createdb, $Dropdb)) {
    if (-not (Test-Path $tool)) {
        throw "PostgreSQL tool tidak ditemukan: $tool"
    }
}

if (-not (Test-Path $EnvFile)) {
    throw ".env tidak ditemukan: $EnvFile"
}

New-Item -ItemType Directory -Force -Path $BackupDirectory | Out-Null

$dbConnection = Get-EnvValue 'DB_CONNECTION'
if ($dbConnection -and $dbConnection.ToLowerInvariant() -ne 'pgsql') {
    throw "Restore ini hanya mendukung PostgreSQL. DB_CONNECTION saat ini: $dbConnection"
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

$backups = Get-ChildItem $BackupDirectory -Filter 'danum-*.dump' -File |
    Sort-Object LastWriteTime -Descending

if ($backups.Count -eq 0) {
    throw "Tidak ada backup .dump di $BackupDirectory"
}

Write-Host ''
Write-Host '========================================' -ForegroundColor Cyan
Write-Host '          DANUM DATABASE RESTORE' -ForegroundColor Cyan
Write-Host '========================================' -ForegroundColor Cyan
Write-Host ''
Write-Host 'Backup tersedia:' -ForegroundColor Yellow

for ($i = 0; $i -lt $backups.Count; $i++) {
    $sizeMb = [math]::Round($backups[$i].Length / 1MB, 2)
    Write-Host "[$($i + 1)] $($backups[$i].Name) - $sizeMb MB"
}

$selection = Read-Host 'Pilih nomor backup untuk restore'
$selectedIndex = 0
if (-not [int]::TryParse($selection, [ref]$selectedIndex) -or
    $selectedIndex -lt 1 -or
    $selectedIndex -gt $backups.Count) {
    throw 'Pilihan backup tidak valid.'
}

$backupFile = $backups[$selectedIndex - 1].FullName

Write-Host ''
Write-Host "Backup dipilih: $backupFile" -ForegroundColor Yellow
Write-Host ''
Write-Host 'Mode restore:' -ForegroundColor Yellow
Write-Host '[1] Restore ke database BARU (AMAN - database DANUM tidak disentuh)'
Write-Host '[2] Restore ke database DANUM AKTIF (BERISIKO - akan mengganti data)'

$mode = Read-Host 'Pilih mode restore'

$env:PGHOST = $dbHost
$env:PGPORT = $dbPort
$env:PGUSER = $dbUsername
$env:PGPASSWORD = $dbPassword

try {
    Write-Host ''
    Write-Host '[VERIFY] Memeriksa integritas backup...' -ForegroundColor Yellow
    & $PgRestore --list "$backupFile" | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Backup tidak dapat dibaca oleh pg_restore. Exit code: $LASTEXITCODE"
    }

    if ($mode -eq '1') {
        $restoreDatabase = Read-Host 'Masukkan nama database TEST yang akan dibuat'

        if ([string]::IsNullOrWhiteSpace($restoreDatabase)) {
            throw 'Nama database test wajib diisi.'
        }

        if ($restoreDatabase -eq $dbDatabase) {
            throw 'Database test tidak boleh sama dengan DB_DATABASE aktif.'
        }

        Write-Host "`n[CREATE] Membuat database: $restoreDatabase" -ForegroundColor Yellow
        & $Createdb --host="$dbHost" --port="$dbPort" --username="$dbUsername" "$restoreDatabase"
        if ($LASTEXITCODE -ne 0) {
            throw "Gagal membuat database test. Exit code: $LASTEXITCODE"
        }

        try {
            Write-Host '[RESTORE] Memulihkan backup ke database test...' -ForegroundColor Yellow
            & $PgRestore --host="$dbHost" --port="$dbPort" --username="$dbUsername" --dbname="$restoreDatabase" --no-owner --exit-on-error "$backupFile"
            if ($LASTEXITCODE -ne 0) {
                throw "Restore ke database test gagal. Exit code: $LASTEXITCODE"
            }
        }
        catch {
            Write-Host '[CLEANUP] Menghapus database test karena restore gagal...' -ForegroundColor Yellow
            & $Dropdb --host="$dbHost" --port="$dbPort" --username="$dbUsername" --if-exists "$restoreDatabase" | Out-Null
            throw
        }

        Write-Host ''
        Write-Host "[OK] Restore test berhasil ke database: $restoreDatabase" -ForegroundColor Green
        Write-Host '[OK] Database DANUM aktif tidak disentuh.' -ForegroundColor Green
        Write-Host 'Sekarang database test dapat diperiksa sebelum melakukan restore production.' -ForegroundColor Green
    }
    elseif ($mode -eq '2') {
        Write-Host '' -ForegroundColor Red
        Write-Host 'PERINGATAN: database DANUM AKTIF akan diganti dari backup.' -ForegroundColor Red
        Write-Host 'Data yang dibuat setelah waktu backup dapat hilang.' -ForegroundColor Red
        $confirmation = Read-Host 'Ketik RESTORE DANUM untuk melanjutkan'

        if ($confirmation -cne 'RESTORE DANUM') {
            throw 'Restore database aktif dibatalkan karena konfirmasi tidak cocok.'
        }

        $emergencyBackup = Join-Path $BackupDirectory ("danum-before-restore-" + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.dump')
        Write-Host '[SAFETY] Membuat backup database aktif sebelum restore...' -ForegroundColor Yellow
        & $PgDump --format=custom --file="$emergencyBackup" --no-password
        if ($LASTEXITCODE -ne 0) {
            throw "Backup pengaman sebelum restore gagal. Restore dibatalkan. Exit code: $LASTEXITCODE"
        }

        Write-Host '[RESTORE] Mengosongkan object database aktif dan memulihkan backup...' -ForegroundColor Yellow
        & $PgRestore --host="$dbHost" --port="$dbPort" --username="$dbUsername" --dbname="$dbDatabase" --clean --if-exists --no-owner --exit-on-error "$backupFile"
        if ($LASTEXITCODE -ne 0) {
            throw "Restore database aktif gagal. Backup pengaman tersedia di: $emergencyBackup. Exit code: $LASTEXITCODE"
        }

        Write-Host ''
        Write-Host '[OK] Restore database DANUM aktif berhasil.' -ForegroundColor Green
        Write-Host "[SAFETY] Backup sebelum restore: $emergencyBackup" -ForegroundColor Yellow
    }
    else {
        throw 'Mode restore tidak valid.'
    }
}
finally {
    Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
    Remove-Item Env:PGHOST -ErrorAction SilentlyContinue
    Remove-Item Env:PGPORT -ErrorAction SilentlyContinue
    Remove-Item Env:PGUSER -ErrorAction SilentlyContinue
}

Write-Host ''
