# Integrar cambios desde `c:\Laboratorio\Laboratorio`

Copia de trabajo del otro dev. **Fuente de verdad de novedades:** `Laboratorio/CAMBIOS.md` (copiado del externo).

**No reemplazar** `c:\Control Salud\Laboratorio` entero: perderíamos integración Control Salud, rutas embebidas y correcciones de producción.

## Rutas

| Carpeta | Rol |
|---------|-----|
| `c:\Laboratorio\Laboratorio\` | Lab “puro” (standalone) |
| `c:\Control Salud\Laboratorio\` | Lab embebido en Control Salud Web |
| `c:\Control Salud\web\public\laboratorio\` | Puente opcional (Nginx Opción A **no lo usa**) |

## Proceso (igual que la última vez)

1. Leer **`CAMBIOS.md`** (sesión por sesión, de arriba hacia abajo).
2. **Diff cherry-pick** — nunca copiar la carpeta entera:

```powershell
$ext = "c:\Laboratorio\Laboratorio"
$cs  = "c:\Control Salud\Laboratorio"

git -C "c:\Control Salud" diff --no-index `
  "$cs\src\Services\PedidoService.php" `
  "$ext\src\Services\PedidoService.php"
```

3. Traer el archivo del externo y **reaplicar a mano** lo de Control Salud (integración CS, columnas `DNI`/`NroHC`, `lista_coberturas`, etc.).
4. En vistas/JS: **`lab_h()`**, **`lab_asset_h()`**, import `../api.js?v=N` (no paths absolutos `/assets/...`).
5. Probar local: `http://localhost:8080/laboratorio/...` (login CS primero).
6. Deploy Gesis2: subir solo archivos tocados bajo **`Laboratorio/`** (sin `.env`, sin `vendor` salvo composer nuevo). **Sin puente** si Nginx Opción A.
7. BD: migrations en orden + seeds si indica `CAMBIOS.md`.

## Qué NO tocar al copiar (solo en Control Salud)

- `src/Integration/ControlSaludIntegration.php`
- `config/session_bridge.php`
- `public/views/_layout/lab_url.php`
- `public/views/_layout/header.php` (meta `lab-api-bridge`, `lab_h()`)
- `public/assets/js/api.js` (`labPath`, `api.php`)
- `public/api.php`, `public/index.php`, `public/request_path.php`
- Puente `web/public/laboratorio/*` (opcional; no obligatorio en prod)

**Header / CSS:** copiar bloques UI nuevos; **no** pisar metas ni script de API del header CS.

---

## Ya integrado en Control Salud (Sesión 1 — A–D)

| Fase | Qué | Estado |
|------|-----|--------|
| **A** | Perfiles con determinaciones (nuevo + planilla) | ✅ |
| **B** | Búsqueda `q` en listado | ✅ |
| **C** | Búsqueda en `/resultados/cargar` | ✅ |
| **D** | N° orden simple + migration `024` | ✅ código; SQL aplicado en local |

Commits locales: `7f641bd` (B+C), `675c318` (D). Push pendiente si no se publicó.

---

## Integrado — Sesión 2 (2026-05-28) ✅

Desplegado local + Gesis2. SQL: **025**, **026**, seed **003** + fixes perfiles y area_id.

Ver checklist en `sql/APLICAR_EN_ORDEN.md`.

---

## Integrado — Sesión 3 (2026-05-29) ✅

Desplegado local + Gesis2. SQL: **027**. Incluye NBU por fecha, re-precio, ficha
paciente, nomenclador, acto bioquímico, NBU por perfil, API valores-referencia.

---

## Integrado — Sesión 4 (2026-06-01) ✅

| # | Tema | Estado |
|---|------|--------|
| 1 | Checkbox acto en nomenclador | ✅ (S3) |
| 2 | ABM rangos referencia | ✅ (S3) |
| 3 | Textos informe PDF en config | ✅ |
| 4 | UX modal nomenclador | ✅ (S3) |

Archivos: `public/views/configuracion/index.php`, `public/assets/js/configuracion/index.js`,
`public/views/informes/template.php`.

---

## Migraciones — estado Control Salud

| Migration / seed | Local | Prod |
|------------------|-------|------|
| 024 | ✅ | ✅ |
| 025–026 | ✅ | ✅ |
| 003 + fixes CS | ✅ | ✅ |
| 027 | ✅ | ✅ |

Orden: ver `sql/APLICAR_EN_ORDEN.md`.

---

## Deploy Gesis2 (recordatorio)

1. Backup BD.
2. SQL migrations que falten.
3. Subir solo `Laboratorio/...` (PHP, vistas, assets, `api/` nuevos).
4. No pisar `.env` ni `vendor`.
5. Ctrl+F5; revisar `?v=` en scripts.

## Orden sugerido de merge

```text
Confirmar A–D en prod ✅
→ Sesión 2 ✅
→ Sesión 3 ✅
→ Sesión 4 ✅
→ Siguiente: validación cliente facturación web (P-FAC-01) / Seguridad P0 web / Caja v2
```

Cada bloque: diff → adaptar CS → probar local → SQL si aplica → FTP Gesis2.
