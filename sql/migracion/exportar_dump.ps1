# Exporta SQL Server -> archivo .sql en sql/migracion/export/
# Uso: clic derecho -> Ejecutar con PowerShell  (o desde terminal en esta carpeta)

$ErrorActionPreference = "Stop"
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $here

Write-Host ""
Write-Host "=== Control Salud: exportar dump desde SQL Server ===" -ForegroundColor Cyan
Write-Host ""

$configEnv = Join-Path $here "config.env"
$configExample = Join-Path $here "config.example.env"
if (-not (Test-Path $configEnv)) {
    if (Test-Path $configExample) {
        Copy-Item $configExample $configEnv
        Write-Host "Se creo config.env desde el ejemplo." -ForegroundColor Yellow
        Write-Host "EDITALO antes de seguir (servidor, base Datos, ruta de salida)." -ForegroundColor Yellow
        Write-Host "  $configEnv"
        Write-Host ""
        notepad $configEnv
        Read-Host "Cuando guardes config.env, apreta Enter para continuar"
    } else {
        throw "No existe config.env ni config.example.env"
    }
}

$exportDir = Join-Path $here "export"
if (-not (Test-Path $exportDir)) {
    New-Item -ItemType Directory -Path $exportDir | Out-Null
}

# Asegurar salida dentro del proyecto si no esta definida en config.env
$cfgText = Get-Content $configEnv -Raw
if ($cfgText -notmatch 'SQLSERVER_TO_MYSQL_OUT=') {
    Add-Content $configEnv "`nSQLSERVER_TO_MYSQL_OUT=$exportDir\datos_desde_sqlserver.sql"
}
if ($cfgText -match 'SQLSERVER_TO_MYSQL_OUT=\s*$' -or $cfgText -notmatch 'SQLSERVER_TO_MYSQL_OUT=.+\\export\\') {
    Write-Host "Tip: en config.env usa por ejemplo:" -ForegroundColor DarkGray
    Write-Host "  SQLSERVER_TO_MYSQL_OUT=$exportDir\datos_desde_sqlserver.sql" -ForegroundColor DarkGray
    Write-Host ""
}

Write-Host "Comprobando Python..."
$py = Get-Command python -ErrorAction SilentlyContinue
if (-not $py) {
    throw "No esta instalado Python. Instalalo desde python.org y volve a ejecutar."
}

Write-Host "Instalando dependencias (pyodbc) si hace falta..."
python -m pip install -q -r (Join-Path $here "requirements.txt")

Write-Host "Exportando (puede tardar varios minutos)..."
python (Join-Path $here "sqlserver_backup_to_mysql_sql.py")
if ($LASTEXITCODE -ne 0) {
    throw "Fallo la exportacion. Revisa config.env (servidor, base, ODBC Driver 18)."
}

Write-Host ""
Write-Host "Listo. Revisa la carpeta:" -ForegroundColor Green
Write-Host "  $exportDir"
Get-ChildItem $exportDir -Filter *.sql | Sort-Object LastWriteTime -Descending | Select-Object -First 3 | Format-Table Name, Length, LastWriteTime
Write-Host "Ese .sql es el dump para importar despues en MySQL." -ForegroundColor Green
Write-Host ""
Read-Host "Enter para cerrar"
