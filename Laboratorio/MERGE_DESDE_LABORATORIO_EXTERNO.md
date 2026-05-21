# Integrar cambios desde `c:\Laboratorio\Laboratorio`

Copia de trabajo del otro dev (2026-05-19, ver `CAMBIOS.md` allí).  
**No reemplazar** `c:\Control Salud\Laboratorio` entero: perderíamos integración Control Salud, puente Nginx y correcciones de producción.

## Rutas

| Carpeta | Rol |
|---------|-----|
| `c:\Laboratorio\Laboratorio\` | Lab “puro” (standalone) |
| `c:\Control Salud\Laboratorio\` | Lab embebido en Control Salud Web |
| `c:\Control Salud\web\public\laboratorio\` | Puente (`api.php`, assets, health) |

## Qué trae el externo (CAMBIOS.md)

| # | Funcionalidad | Impacto integración |
|---|---------------|---------------------|
| 1 | Perfiles muestran determinaciones en nuevo pedido + planilla | Solo front |
| 2 | Búsqueda rápida en listado (`q` = DNI, nombre, HC, N° orden) | Backend + vista + CSS |
| 3 | Búsqueda en `/resultados/cargar` (dropdown de pedidos) | Vista + JS |
| 4 | N° orden simple + migration `024` | **BD compartida** — coordinar antes de prod |

## Qué NO tocar al copiar (solo en Control Salud)

- `src/Integration/ControlSaludIntegration.php`
- `config/session_bridge.php`
- `public/views/_layout/lab_url.php`
- `public/views/_layout/header.php` (meta `lab-api-bridge`, fetch wrapper, `lab_h()`)
- `public/assets/js/api.js` (versión + `labPath` / `api.php`)
- `public/request_path.php`, `public/serve_static.php`
- `public/index.php` (rutas embebidas)
- Puente `web/public/laboratorio/*`

**Estrategia para `header.php`:** copiar solo bloques de UI/CSS nuevos; **reaplicar a mano** metas y script de API del header de Control Salud.

## Archivos a traer (cherry-pick)

### Fase A — Solo front ✅ en `main`

1. `public/assets/js/pedidos/nuevo.js` — perfiles con determinaciones  
2. `public/assets/js/pedidos/listado.js` — planilla `perfil-detalle`  
3. `public/assets/css/app.css` — `.perfil-item`, `.perfil-detalle`

### Fase B — Listado con `q` ✅

1. `public/views/pedidos/listado.php` — buscador + `<details>` más filtros  
2. `public/assets/css/app.css` — `.busqueda-rapida`, `.filtros-avanzados`  
3. `src/Services/PedidoService.php` — filtro `q`  
4. `src/Repositories/PedidoRepository.php` — `q` con columnas Control Salud

### Fase C — Resultados ✅

1. `public/views/resultados/cargar.php` — buscador rápido + `lab_asset_h` / versión scripts  
2. `public/assets/js/resultados/cargar.js` — dropdown + filtro estados cargables + `../api.js`  
3. `public/assets/css/app.css` — `.buscador-pedido`, `.dropdown-resultados`

### Fase D — N° orden (solo con acuerdo)

1. Copiar `sql/migrations/024_numero_orden_simple.sql` → Control Salud  
2. `src/Services/PedidoService.php` — formato número (externo usa correlativo simple)  
3. Actualizar `sql/install/lab_schema.sql` si aplica  
4. **Producción:** backup + ventana; PDFs viejos conservan número anterior

Copiar también `CAMBIOS.md` del externo a este repo como referencia histórica (opcional).

## Cómo trabajar en la práctica

```powershell
$ext = "c:\Laboratorio\Laboratorio"
$cs  = "c:\Control Salud\Laboratorio"

# Ejemplo: diff de un archivo antes de copiar
git -C "c:\Control Salud" diff --no-index `
  "$cs\public\assets\js\pedidos\nuevo.js" `
  "$ext\public\assets\js\pedidos\nuevo.js"
```

Después de cada fase:

1. `.\web\deploy\copy-lab-assets.ps1` (sincroniza assets al puente)  
2. Probar local: `/laboratorio/?r=pedidos.nuevo`, listado, resultados  
3. En prod: solo subir archivos tocados + migration si corresponde

## Estado actual (comparación rápida)

- **Solo en externo:** `CAMBIOS.md`, `024_numero_orden_simple.sql`  
- **Solo en Control Salud:** integración CS, `lab_url`, `session_bridge`, puente web  
- **Distinto contenido:** ~176 archivos (muchos por CRLF o versión base); priorizar los listados en CAMBIOS.md

## Orden recomendado

```text
A (perfiles UI) → B (listado q) → C (resultados) → copy-lab-assets → deploy
→ D (024) solo cuando coordinen producción e impresos
```
