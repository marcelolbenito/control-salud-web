# Checklist — subir a producción (Gesis2) lo trabajado en local

Estado local (Docker): datos MVP + migraciones 038–040 aplicadas.  
Producción: 034–037 ya hechas; falta código nuevo + 038–040 + dump MVP.

## 0) Antes de tocar el servidor

- [ ] Commit / push a `origin/main` del código MVP (FE, novedades, HC, facturación sin precio).
- [ ] Backup BD prod.
- [ ] Probar smoke mínimo en local (opcional si ya validaron).

## 1) Código (PHP)

En el servidor (`/home/ubuntu/entorno_web/web-php8/clinica/` o la ruta real):

```bash
cd /ruta/a/clinica
git pull origin main
mkdir -p storage/novedades
# permisos según user del contenedor PHP (ej. www-data)
```

Incluye:

- Facturación OS + **Sin precio OS**
- Código nomenclador (037)
- Agenda clic/doble clic
- HC con origen exe (038)
- **Info / Novedades** (039)
- **Facturación electrónica** Gesis (040)
- Ayuda / menú

## 2) Migraciones schema en prod (si el dump no las trae)

Orden recomendado (phpMyAdmin o mysql CLI):

1. [ ] `sql/migration_039_novedades.sql`
2. [ ] `sql/migration_040_factura_electronica.sql`
3. [ ] HC:
   - **Preferido:** el dump MVP ya trae `pacientes_hc_notas` con notas del exe → no hace falta 038 import.
   - **Alternativa:** importar tabla `Consultas` + `sql/migration_038_consultas_a_hc_notas_manual.sql`

Ya hechas antes: **034, 035, 036, 037**.

## 3) Datos MVP (dump local → Gesis2)

```powershell
cd "C:\Control Salud\sql\migracion"
.\exportar_mvp_desde_local.ps1
```

Incluye (si existen): coberturas, planes, prácticas, precios, pacientes,  
`pacientes_hc_notas`, órdenes (`Pacientes Ordenes`), turnos, `Consultas`,  
`fe_parametros` / `fe_comprobantes`, `novedades_archivos`, etc.

```bash
scp "mvp_gesis2_....sql" ubuntu@ssh.gesis2.com:/tmp/mvp_gesis2.sql
# backup prod primero
docker exec -i mysqldb mysql -h 127.0.0.1 -u USER -p NOMBRE_BD < /tmp/mvp_gesis2.sql
```

**Ojo:** el dump hace `DROP`+`CREATE` de esas tablas (pisa datos prod de esas tablas).

## 4) Post-subida

- [ ] `storage/novedades` escribible
- [ ] Login admin
- [ ] Info / Novedades: subir PDF
- [ ] HC paciente con notas “Sistema anterior”
- [ ] Orden 420101 + Swiss/Sancor
- [ ] Facturación OS: filas amarillas sin precio
- [ ] FE → Parámetros: URL Gesis + email/pass (homologación) — requiere negocio/certs en Gesis2
- [ ] Agenda: clic horario

## 5) No subir

- `config.local.php`, secretos, `node_modules`, `Datos.bak`, logos sueltos no versionados

## Día D (corto)

```
1. Backup BD prod
2. git pull
3. mkdir storage/novedades + permisos
4. Import dump MVP
5. Si hace falta: 039 + 040 (039/040 también vienen vacíos en dump si se exportaron)
6. Configurar FE parámetros (Gesis)
7. Smoke test
```
