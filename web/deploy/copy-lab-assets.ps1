# Copia CSS/JS del lab al puente (Nginx sirve archivos físicos en /laboratorio/assets/)
$root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
$src = Join-Path $root 'Laboratorio\public\assets'
$dst = Join-Path $root 'web\public\laboratorio\assets'
if (-not (Test-Path $src)) {
    Write-Error "No existe $src"
    exit 1
}
New-Item -ItemType Directory -Force -Path $dst | Out-Null
Copy-Item -Path (Join-Path $src '*') -Destination $dst -Recurse -Force
Write-Host "OK: $dst"
