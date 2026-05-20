# Instala dependencias del modulo Laboratorio y opcionalmente el esquema lab_* en MySQL (Docker).
# Reversible: no modifica tablas legacy; solo CREATE de tablas lab_*.

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$Lab = Join-Path $Root 'Laboratorio'

Write-Host '==> composer install (Laboratorio)' -ForegroundColor Cyan
Push-Location $Lab
try {
    $didComposer = $false
    if (Get-Command composer -ErrorAction SilentlyContinue) {
        try {
            composer install --no-interaction
            $didComposer = $true
        } catch {
            Write-Warning "composer local fallo (PHP host?). Se intentara en Docker."
        }
    }
    if (-not $didComposer) {
        Write-Host '==> composer install dentro de control-salud-web ...' -ForegroundColor Cyan
        docker compose -f (Join-Path $Root 'docker-compose.yml') exec -T webapp composer install -d /var/www/laboratorio --no-interaction --ignore-platform-req=php-64bit
    }

    $envFile = Join-Path $Lab '.env'
    $envExample = Join-Path $Lab '.env.example'
    if (-not (Test-Path $envFile) -and (Test-Path $envExample)) {
        Copy-Item $envExample $envFile
        Write-Host 'Creado Laboratorio/.env desde .env.example' -ForegroundColor Green
    }
} finally {
    Pop-Location
}

$schema = Join-Path $Lab 'sql\install\lab_schema.sql'
if (-not (Test-Path $schema)) {
    Write-Warning "No se encontro $schema"
    exit 0
}

$apply = Read-Host 'Aplicar lab_schema.sql en Docker control-salud-mysql? (s/N)'
if ($apply -match '^[sS]') {
    Write-Host '==> Importando esquema lab_* ...' -ForegroundColor Cyan
    Get-Content $schema -Raw | docker exec -i control-salud-mysql mysql -uroot -psalud_root_dev control_salud
    Write-Host 'Listo. Reinicie el contenedor web si cambio apache-vhost: docker compose restart webapp' -ForegroundColor Green
}

Write-Host @'

Pasos manuales:
  1. En web/config/config.local.php: laboratorio.enabled = true
  2. docker compose restart webapp
  3. Probar http://localhost:8080/laboratorio/health (logueado en la web)

Para desactivar: laboratorio.enabled = false y/o LAB_INTEGRATION vacio en Laboratorio/.env

'@
