# SQL — qué correr y en qué orden

Aplicar **a mano**, en orden. SQL **antes** que subir el PHP que lo usa.

Base local Docker: `control_salud` (contenedor `control-salud-mysql`).

---

## Checklist rápido

| Paso | Archivo | Sesión | Local | Prod (Gesis2) |
|------|---------|--------|-------|---------------|
| 1 | `migrations/024_numero_orden_simple.sql` | 1 | ✅ | ✅ |
| 2 | `migrations/025_paciente_id_opcional.sql` | 2 | ✅ | ✅ |
| 3 | `migrations/026_texto_referencia_text.sql` | 2 | ✅ | ✅ |
| 4 | `seeds/003_catalogo_unidades_referencias.sql` | 2 | ✅ | ✅ |
| 5 | `seeds/003_fix_perfiles_control_salud.sql` | 2 | ✅ | ✅ |
| 6 | `seeds/003_fix_area_ids_control_salud.sql` | 2 | ✅ | ✅ |
| 7 | `migrations/027_acto_facturacion_y_nbu_perfil.sql` | 3 | ✅ | ✅ |

---

## Qué hace cada una

### 025 — paciente opcional (Sesión 2)
`lab_pedidos.paciente_id` pasa a NULL. Obligatoria **antes** del PHP de pedidos sin paciente fijo.

### 026 — referencias largas (Sesión 2)
`lab_valores_referencia.texto_referencia`: VARCHAR(200) → TEXT.  
Obligatoria **antes** del seed `003` (textos multilínea del catálogo).

### Seed 003 — catálogo unidades + referencias (Sesión 2)
Actualiza unidades, métodos, referencias del cliente, fórmula leucocitaria.  
Requiere seeds `001` y `002` ya cargados. Idempotente (`REPLACE`).

### Fix perfiles (Control Salud)
`003_fix_perfiles_control_salud.sql` — el seed 003 asume `perfil_id` 1–5; en CS suelen
estar en 6–10. Rearma HMG con 14 ítems (fórmula leucocitaria).

### Fix area_id (Control Salud)
`003_fix_area_ids_control_salud.sql` — si `lab_areas` se recreó con IDs distintos
(7–11 en vez de 1–6), el JOIN del catálogo devuelve 0 filas y **nuevo pedido queda
sin determinaciones**. Remapea por código de área (HEM, QC, …, OTR).

### 027 — acto bioquímico + NBU por perfil (Sesión 3)
Agrega `solo_facturacion` y `lab_perfiles.nbu_unidades`. Correr **antes** del PHP
de Sesión 3 (NBU por unidades, acto automático, nomenclador).

---

## Comandos — local (Docker)

Desde la raíz del repo (`c:\Control Salud`):

```powershell
Get-Content "Laboratorio\sql\migrations\025_paciente_id_opcional.sql" -Raw |
  docker exec -i control-salud-mysql mysql -uroot -psalud_root_dev control_salud

Get-Content "Laboratorio\sql\migrations\026_texto_referencia_text.sql" -Raw |
  docker exec -i control-salud-mysql mysql -uroot -psalud_root_dev control_salud

Get-Content "Laboratorio\sql\seeds\003_catalogo_unidades_referencias.sql" -Raw |
  docker exec -i control-salud-mysql mysql -uroot -psalud_root_dev control_salud

Get-Content "Laboratorio\sql\seeds\003_fix_perfiles_control_salud.sql" -Raw |
  docker exec -i control-salud-mysql mysql -uroot -psalud_root_dev control_salud

Get-Content "Laboratorio\sql\seeds\003_fix_area_ids_control_salud.sql" -Raw |
  docker exec -i control-salud-mysql mysql -uroot -psalud_root_dev control_salud

Get-Content "Laboratorio\sql\migrations\027_acto_facturacion_y_nbu_perfil.sql" -Raw |
  docker exec -i control-salud-mysql mysql -uroot -psalud_root_dev control_salud
```

---

## Comandos — producción (Gesis2)

Mismo orden. Desde SSH, vía `docker exec -i mysqldb mysql … control_salud`.

**No repetir** pasos ya aplicados; los fixes 003 son idempotentes.

---

## Deploy PHP (después del SQL)

1. Subir `Laboratorio/` (api, src, public). No pisar `.env` ni `vendor`.
2. Ctrl+F5; scripts con `?v=12`.
3. Smoke: nuevo pedido (catálogo), perfiles, nomenclador, informes PDF, lote OS.
