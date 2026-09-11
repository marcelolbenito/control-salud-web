# Exporta desde MySQL local (Docker) el set MVP para subir a Gesis2.
# Incluye catálogos (coberturas, planes, prácticas, precios) + doctores/pacientes/turnos web
# y tablas legacy usadas por sync si existen.
#
# Uso (con control-salud-mysql levantado):
#   cd "C:\Control Salud\sql\migracion"
#   .\exportar_mvp_desde_local.ps1
#
# Salida: sql\migracion\export\mvp_gesis2_YYYYMMDD_HHMM.sql

$ErrorActionPreference = "Stop"
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = Split-Path -Parent (Split-Path -Parent $here)
$exportDir = Join-Path $here "export"
if (-not (Test-Path $exportDir)) {
    New-Item -ItemType Directory -Path $exportDir | Out-Null
}

$container = "control-salud-mysql"
$stamp = Get-Date -Format "yyyyMMdd_HHmm"
$outFile = Join-Path $exportDir "mvp_gesis2_$stamp.sql"

Write-Host ""
Write-Host "=== Export MVP desde Docker local ===" -ForegroundColor Cyan

$running = docker ps --format "{{.Names}}" | Where-Object { $_ -eq $container }
if (-not $running) {
    Write-Host "Levantando $container ..." -ForegroundColor Yellow
    Set-Location $root
    docker compose up -d mysql
    Start-Sleep -Seconds 5
}

# Tablas web + legacy típicas del exe (si no existen, mysqldump las omite con --force? mejor listar existentes)
$wanted = @(
    "lista_coberturas",
    "lista_planes",
    "lista_practicas",
    "lista_precios",
    "lista_doctores",
    "lista_derivaciones",
    "lista_sucursales",
    "lista_motivos",
    "lista_primera_vez",
    "pacientes",
    "pacientes_hc_notas",
    "pacientes_hc_adjuntos",
    "agenda_turnos",
    "agenda_bloqueos",
    "usuarios",
    "clinicas",
    "config",
    "novedades_archivos",
    "fe_parametros",
    "fe_comprobantes",
    "Lista Coberturas",
    "Lista Planes",
    "Lista Precios",
    "Lista Doctores",
    "Lista Nomenclador",
    "Lista Practicas",
    "Pacientes",
    "Pacientes Ordenes",
    "Agenda Turnos",
    "Consultas"
)

$existingRaw = docker exec $container mysql -uroot -psalud_root_dev -N -e "SHOW TABLES FROM control_salud" 2>$null
if (-not $existingRaw) {
    throw "No se pudo listar tablas en control_salud. ¿Está levantado $container?"
}
$existing = @{}
foreach ($line in ($existingRaw -split "`n")) {
    $t = $line.Trim()
    if ($t -ne "") { $existing[$t] = $true }
}

$tables = @()
foreach ($t in $wanted) {
    if ($existing.ContainsKey($t)) {
        $tables += $t
    } else {
        Write-Host "  (omitida, no existe) $t" -ForegroundColor DarkGray
    }
}

if ($tables.Count -lt 1) {
    throw "No hay tablas para exportar."
}

Write-Host "Exportando $($tables.Count) tablas → $outFile" -ForegroundColor Green

# mysqldump con nombres entre comillas para tablas con espacios
$quoted = ($tables | ForEach-Object { '`' + ($_ -replace '`','``') + '`' }) -join " "
# docker exec: pasar lista sin backticks problemáticos — usar --tables y nombres
$argsList = @(
    "exec", $container,
    "mysqldump",
    "-uroot", "-psalud_root_dev",
    "--single-transaction",
    "--routines=false",
    "--triggers=false",
    "--default-character-set=utf8mb4",
    "--hex-blob",
    "control_salud"
) + $tables

$dump = & docker @argsList 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host $dump
    throw "mysqldump falló (exit $LASTEXITCODE)"
}

# Filtrar warning de password de stderr mezclado
$lines = $dump | Where-Object { $_ -is [string] -and $_ -notmatch "Using a password on the command line" }
[System.IO.File]::WriteAllLines($outFile, $lines, [System.Text.UTF8Encoding]::new($false))

$mb = [math]::Round((Get-Item $outFile).Length / 1MB, 2)
Write-Host ""
Write-Host "Listo: $outFile ($mb MB)" -ForegroundColor Green
Write-Host ""
Write-Host "=== Subir a Gesis2 ===" -ForegroundColor Yellow
Write-Host "scp `"$outFile`" ubuntu@ssh.gesis2.com:/tmp/mvp_gesis2.sql"
Write-Host ""
Write-Host "=== Import en prod (pisa tablas del dump) ===" -ForegroundColor Yellow
Write-Host "docker exec -i mysqldb mysql -h 127.0.0.1 -u marcelo -p control_salud < /tmp/mvp_gesis2.sql"
Write-Host ""
Write-Host "Antes en prod: backup opcional. Después: verificar conteos en lista_practicas, lista_precios, pacientes, agenda_turnos."
Write-Host ""
