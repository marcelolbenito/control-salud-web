# Prepara archivos .sql para importar en produccion (doctores, pacientes, turnos >= 2026).
#
# Uso:
#   .\preparar_legacy_para_produccion.ps1              # export SQL Server + partir
#   .\preparar_legacy_para_produccion.ps1 -SkipExport  # solo partir dump ya generado
#
# Ver: PROCEDIMIENTO_DUMP_BAK_PRODUCCION.md

param(
    [switch]$SkipExport
)

$ErrorActionPreference = "Stop"
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$exportDir = Join-Path $here "export"
Set-Location $here

function Invoke-PythonStep {
    param(
        [string]$Label,
        [string]$ScriptPath
    )
    Write-Host ""
    Write-Host ">>> $Label" -ForegroundColor Cyan
    python $ScriptPath
    if ($LASTEXITCODE -ne 0) {
        throw "Fallo: $ScriptPath"
    }
}

Write-Host ""
Write-Host "=== Control Salud: preparar legacy para produccion ===" -ForegroundColor Cyan
Write-Host ""

$configEnv = Join-Path $here "config.env"
if (-not (Test-Path $configEnv)) {
    $example = Join-Path $here "config.example.env"
    if (Test-Path $example) {
        Copy-Item $example $configEnv
        Write-Host "Se creo config.env. Editalo y volve a ejecutar." -ForegroundColor Yellow
        notepad $configEnv
        exit 1
    }
    throw "No existe config.env"
}

if (-not (Test-Path $exportDir)) {
    New-Item -ItemType Directory -Path $exportDir | Out-Null
}

$py = Get-Command python -ErrorAction SilentlyContinue
if (-not $py) {
    throw "Python no esta en PATH. Instalalo desde python.org."
}

Write-Host "Instalando dependencias si hace falta..."
python -m pip install -q -r (Join-Path $here "requirements.txt")

$dumpCompleto = Join-Path $exportDir "datos_legacy_Pacientes_Doctores_Turnos.sql"
$outDoctores = Join-Path $exportDir "datos_legacy_doctores.sql"
$outPacientes = Join-Path $exportDir "datos_legacy_pacientes.sql"
$outTurnos = Join-Path $exportDir "datos_legacy_turnos_fixed.sql"
$migration033 = Join-Path (Split-Path $here -Parent) "migration_033_merge_legacy_into_web_sin_truncar.sql"

if (-not $SkipExport) {
    Invoke-PythonStep -Label "1/3 Exportar desde SQL Server (varios minutos)" `
        -ScriptPath (Join-Path $here "sqlserver_backup_to_mysql_sql.py")
} else {
    Write-Host "Omitiendo export (-SkipExport)." -ForegroundColor DarkGray
    if (-not (Test-Path $dumpCompleto)) {
        throw "No existe el dump intermedio: $dumpCompleto`nQuita -SkipExport o genera el dump antes."
    }
}

if (-not (Test-Path $dumpCompleto)) {
    throw "No se genero: $dumpCompleto"
}

Push-Location $exportDir
try {
    Invoke-PythonStep -Label "2/3 Extraer doctores y pacientes" `
        -ScriptPath (Join-Path $exportDir "extract_doctores_pacientes_from_dump.py")
    Invoke-PythonStep -Label "3/3 Generar turnos (Fecha >= 2026, ver rebuild_turnos_from_dump.py)" `
        -ScriptPath (Join-Path $exportDir "rebuild_turnos_from_dump.py")
} finally {
    Pop-Location
}

$archivos = @(
    @{ Path = $outDoctores; Nombre = "doctores" },
    @{ Path = $outPacientes; Nombre = "pacientes" },
    @{ Path = $outTurnos; Nombre = "turnos (fixed)" },
    @{ Path = $migration033; Nombre = "migration_033" }
)

Write-Host ""
Write-Host "=== Archivos listos ===" -ForegroundColor Green
foreach ($a in $archivos) {
    if (-not (Test-Path $a.Path)) {
        throw "Falta archivo: $($a.Path)"
    }
    $mb = [math]::Round((Get-Item $a.Path).Length / 1MB, 2)
    Write-Host ("  {0,-12} {1,8} MB  {2}" -f $a.Nombre, $mb, $a.Path)
}

Write-Host ""
Write-Host "=== Subir al servidor (PowerShell, una linea cada una) ===" -ForegroundColor Yellow
Write-Host @"
scp "$outDoctores" ubuntu@ssh.gesis2.com:/tmp/
scp "$outPacientes" ubuntu@ssh.gesis2.com:/tmp/
scp "$outTurnos" ubuntu@ssh.gesis2.com:/tmp/datos_legacy_turnos_fixed.sql
scp "$migration033" ubuntu@ssh.gesis2.com:/tmp/
"@

Write-Host "=== Import en servidor (orden) ===" -ForegroundColor Yellow
Write-Host @"
docker exec -i mysqldb mysql -h 127.0.0.1 -u marcelo -p control_salud < /tmp/datos_legacy_doctores.sql
docker exec -i mysqldb mysql -h 127.0.0.1 -u marcelo -p control_salud < /tmp/datos_legacy_pacientes.sql
docker exec -i mysqldb mysql -h 127.0.0.1 -u marcelo -p control_salud < /tmp/datos_legacy_turnos_fixed.sql
docker exec -i mysqldb mysql -h 127.0.0.1 -u marcelo -p control_salud < /tmp/migration_033_merge_legacy_into_web_sin_truncar.sql
"@

Write-Host "Detalle completo: $here\PROCEDIMIENTO_DUMP_BAK_PRODUCCION.md" -ForegroundColor DarkGray
Write-Host ""
