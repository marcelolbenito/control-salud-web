# Prepara el puente del laboratorio para subir por FTP (assets + lista de archivos).
$ErrorActionPreference = 'Stop'
$root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent

Write-Host '=== Laboratorio - preparar despliegue ===' -ForegroundColor Cyan

& (Join-Path $PSScriptRoot 'copy-lab-assets.ps1')

$bridge = Join-Path $root 'web\public\laboratorio'
$required = @(
    'index.php',
    'resolve_lab.php',
    'api.php',
    'api-ping.php',
    'health.php',
    'probe.php',
    'assets\css\app.css',
    'assets\js\api.js'
)

Write-Host ''
Write-Host "Puente ($bridge):" -ForegroundColor Yellow
$missing = @()
foreach ($rel in $required) {
    $path = Join-Path $bridge $rel
    if (Test-Path $path) {
        Write-Host "  OK  $rel"
    } else {
        Write-Host "  FALTA  $rel" -ForegroundColor Red
        $missing += $rel
    }
}

if ($missing.Count -gt 0) {
    Write-Host ''
    Write-Host 'Faltan archivos en el puente. Actualice el repo y vuelva a ejecutar.' -ForegroundColor Red
    exit 1
}

Write-Host ''
Write-Host 'Subir al servidor:' -ForegroundColor Green
Write-Host '  1. Laboratorio/  ->  .../clinica/Laboratorio/  (carpeta completa)'
Write-Host '  2. web/public/laboratorio/  ->  .../clinica/public/laboratorio/'
Write-Host '  3. web/public/check_lab_setup.php, lab_health.php  ->  public/'
Write-Host ''
Write-Host 'Nginx: enviar al hosting web/deploy/nginx-laboratorio-produccion.conf (Opcion A)'
Write-Host 'Guia:  web/deploy/DESPLIEGUE_LABORATORIO.md'
Write-Host ''
Write-Host 'Listo.' -ForegroundColor Green
